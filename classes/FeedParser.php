<?php

namespace Grav\Plugin\FeedTeasers;

use Grav\Plugin\FeedTeasers\Http\SsrfGuard;

// Manuelles require statt Composer-Autoload: composer.json des Plugins
// enthaelt bewusst keine "require"-Fremdpakete und dient nur als Metadaten
// (siehe docs/ARCHITECTURE.md) - ein vendor/autoload.php ist bei einer
// GPM-Installation nicht garantiert vorhanden.
require_once __DIR__ . '/Http/SsrfGuard.php';

/**
 * Minimaler, abhaengigkeitsfreier RSS 2.0 / Atom 1.0 Parser.
 * Nutzt ausschliesslich in PHP eingebaute Erweiterungen (SimpleXML, libxml).
 */
class FeedParser
{
    private const MAX_REDIRECTS = 5;

    /**
     * Laedt einen Feed per HTTP und gibt ein normalisiertes Array von Items zurueck.
     *
     * @param string   $url
     * @param int      $timeout
     * @param string[] $allowedPrivateHosts Opt-in-Liste bewusst erlaubter
     *        privater/lokaler Hosts (siehe SsrfGuard), normalerweise leer.
     * @throws \RuntimeException wenn der Feed nicht geladen oder geparst werden kann
     * @return array
     */
    public static function fetchAndParse(string $url, int $timeout = 8, array $allowedPrivateHosts = []): array
    {
        $xmlString = self::httpGet($url, $timeout, $allowedPrivateHosts);

        if ($xmlString === null || trim($xmlString) === '') {
            throw new \RuntimeException("Leere Antwort von Feed-URL: {$url}");
        }

        return self::parse($xmlString);
    }

    /**
     * Fuehrt den eigentlichen HTTP-Abruf durch (cURL, falls verfuegbar, sonst
     * stream-basiertes file_get_contents als Fallback).
     *
     * SSRF-Schutz: Jede tatsaechlich kontaktierte URL - inklusive jedes
     * einzelnen Redirect-Ziels - wird ueber SsrfGuard geprueft (Schema,
     * aufgeloeste IP gegen private/reservierte Bereiche). Redirects werden
     * deshalb bewusst NICHT automatisch von cURL verfolgt
     * (CURLOPT_FOLLOWLOCATION => false), sondern manuell Schritt fuer
     * Schritt, jeweils mit erneuter Pruefung des naechsten Ziels. Beim
     * cURL-Pfad wird zusaetzlich die geprüfte IP per CURLOPT_RESOLVE
     * gepinnt, damit tatsaechlich die geprüfte Adresse kontaktiert wird
     * (Schutz gegen DNS-Rebinding zwischen Pruefung und Verbindungsaufbau).
     */
    private static function httpGet(string $url, int $timeout, array $allowedPrivateHosts = []): ?string
    {
        $userAgent = 'GravFeedTeasersPlugin/1.0 (+https://getgrav.org)';
        $guard = new SsrfGuard($allowedPrivateHosts);

        $currentUrl = $url;
        $visited = [];

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            if (isset($visited[$currentUrl])) {
                throw new \RuntimeException("Redirect-Schleife erkannt bei: {$currentUrl}");
            }
            $visited[$currentUrl] = true;

            // Wirft RuntimeException, wenn Schema/Host/aufgeloeste IP nicht
            // erlaubt sind - wird vom Aufrufer (getFeedItems()) abgefangen.
            $ip = $guard->assertAllowedAndResolve($currentUrl);

            if (function_exists('curl_init')) {
                [$status, $location, $body] = self::curlRequestOnce($currentUrl, $ip, $timeout, $userAgent);
            } else {
                [$status, $location, $body] = self::streamRequestOnce($currentUrl, $timeout, $userAgent);
            }

            if (in_array($status, [301, 302, 303, 307, 308], true) && $location !== null && $location !== '') {
                $currentUrl = self::resolveRedirectUrl($currentUrl, $location);
                continue;
            }

            return $body;
        }

        throw new \RuntimeException(
            'Zu viele Redirects (> ' . self::MAX_REDIRECTS . ') beim Laden von: ' . $url
        );
    }

    /**
     * Fuehrt genau einen HTTP-Request per cURL aus, ohne automatischer
     * Redirect-Verfolgung, gepinnt auf die vorab gepruefte IP.
     *
     * @return array{0:int,1:?string,2:string} [http_status, location_header_oder_null, body]
     */
    private static function curlRequestOnce(string $url, string $ip, int $timeout, string $userAgent): array
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        if ($port === null) {
            $port = $scheme === 'https' ? 443 : 80;
        }

        // IPv6-Literale brauchen bei --resolve/CURLOPT_RESOLVE eckige Klammern.
        $resolveIp = (strpos($ip, ':') !== false) ? '[' . $ip . ']' : $ip;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_RESOLVE        => ["{$host}:{$port}:{$resolveIp}"],
        ]);

        $raw = curl_exec($ch);
        $error = curl_error($ch);

        if ($raw === false) {
            curl_close($ch);
            throw new \RuntimeException("cURL-Fehler beim Laden von {$url}: {$error}");
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerStr = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        $location = null;
        // Bei mehreren Redirect-Antworten in $headerStr (kommt bei manuellem
        // Aufruf hier nicht vor, da FOLLOWLOCATION aus ist) zaehlt ohnehin
        // nur der letzte Header-Block - preg_match liefert das letzte
        // Vorkommen durch das 'm'-Flag ueber alle Zeilen ausreichend sicher,
        // da pro Aufruf nur ein Response-Header-Block vorliegt.
        if (preg_match('/^Location:\s*(.+)$/mi', $headerStr, $m)) {
            $location = trim($m[1]);
        }

        return [$status, $location, $body];
    }

    /**
     * Fallback ohne cURL. Kann die Ziel-IP mangels einfacher
     * Stream-Context-Option NICHT pinnen (kein Aequivalent zu
     * CURLOPT_RESOLVE) - die vorab per SsrfGuard geprueften Bereiche
     * werden dadurch weiterhin durchgesetzt, ein sehr eng getaktetes
     * DNS-Rebinding zwischen Pruefung und Verbindungsaufbau ist auf diesem
     * Pfad aber theoretisch nicht ausgeschlossen. In der Praxis nur
     * relevant, wenn cURL auf dem Server tatsaechlich fehlt.
     *
     * @return array{0:int,1:?string,2:string} [http_status, location_header_oder_null, body]
     */
    private static function streamRequestOnce(string $url, int $timeout, string $userAgent): array
    {
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => $timeout,
                'header'          => "User-Agent: {$userAgent}\r\n",
                'follow_location' => 0,
                'ignore_errors'   => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new \RuntimeException("Konnte Feed nicht laden: {$url}");
        }

        $status = 0;
        $location = null;

        // $http_response_header wird von file_get_contents() bei Nutzung
        // des http(s)-Wrappers automatisch im lokalen Scope bereitgestellt.
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $headerLine) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $headerLine, $m)) {
                    $status = (int) $m[1];
                }
                if (preg_match('/^Location:\s*(.+)$/i', $headerLine, $m)) {
                    $location = trim($m[1]);
                }
            }
        }

        return [$status, $location, $body];
    }

    /**
     * Loest ein Location-Header-Ziel (absolut oder relativ) gegen die
     * Basis-URL des vorherigen Hops auf.
     */
    private static function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        $port = isset($base['port']) ? ':' . $base['port'] : '';

        if (substr($location, 0, 2) === '//') {
            return $scheme . ':' . $location;
        }

        if (substr($location, 0, 1) === '/') {
            return $scheme . '://' . $host . $port . $location;
        }

        $basePath = $base['path'] ?? '/';
        $slashPos = strrpos($basePath, '/');
        $baseDir = $slashPos !== false ? substr($basePath, 0, $slashPos + 1) : '/';

        return $scheme . '://' . $host . $port . $baseDir . $location;
    }

    /**
     * Parst RSS 2.0 oder Atom 1.0 XML in ein einheitliches Array-Format:
     * [ ['title' => ..., 'link' => ..., 'date' => int|null, 'summary' => ..., 'image' => string|null], ... ]
     */
    public static function parse(string $xmlString): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            $msg = !empty($errors) ? trim($errors[0]->message) : 'unbekannter XML-Fehler';
            throw new \RuntimeException("Feed konnte nicht geparst werden: {$msg}");
        }

        $root = $xml->getName();

        if ($root === 'feed') {
            return self::parseAtom($xml);
        }

        if ($root === 'rss' || $root === 'RDF') {
            return self::parseRss($xml);
        }

        throw new \RuntimeException("Unbekanntes Feed-Format (Root-Element: {$root})");
    }

    private static function parseRss(\SimpleXMLElement $xml): array
    {
        $items = $xml->channel->item ?? $xml->item ?? [];
        $namespaces = $xml->getNamespaces(true);
        $result = [];

        foreach ($items as $item) {
            $content = $item->children($namespaces['content'] ?? 'content')->encoded ?? '';
            $description = (string) ($item->description ?? '');
            $rawContent = (string) $content ?: $description;

            $image = self::extractRssImage($item, $namespaces, $rawContent);

            $result[] = [
                'title'   => trim((string) ($item->title ?? '')),
                'link'    => trim((string) ($item->link ?? '')),
                'date'    => self::parseDate((string) ($item->pubDate ?? '')),
                'summary' => self::stripToText($description !== '' ? $description : $rawContent),
                'image'   => $image,
            ];
        }

        return $result;
    }

    private static function parseAtom(\SimpleXMLElement $xml): array
    {
        $namespaces = $xml->getNamespaces(true);
        $result = [];

        foreach ($xml->entry as $entry) {
            $link = self::extractAtomLink($entry);
            $summary = (string) ($entry->summary ?? $entry->content ?? '');
            $image = self::extractAtomImage($entry, $namespaces, $summary);
            $dateStr = (string) ($entry->published ?? $entry->updated ?? '');

            $result[] = [
                'title'   => trim((string) ($entry->title ?? '')),
                'link'    => $link,
                'date'    => self::parseDate($dateStr),
                'summary' => self::stripToText($summary),
                'image'   => $image,
            ];
        }

        return $result;
    }

    private static function extractAtomLink(\SimpleXMLElement $entry): string
    {
        $fallback = '';

        foreach ($entry->link as $link) {
            $attrs = $link->attributes();
            $rel = (string) ($attrs['rel'] ?? 'alternate');
            $href = (string) ($attrs['href'] ?? '');

            if ($href === '') {
                continue;
            }
            if ($rel === 'alternate') {
                return $href;
            }
            if ($fallback === '') {
                $fallback = $href;
            }
        }

        return $fallback;
    }

    private static function extractRssImage(\SimpleXMLElement $item, array $namespaces, string $htmlContent): ?string
    {
        // 1) <enclosure url="..." type="image/...">
        if (isset($item->enclosure)) {
            $attrs = $item->enclosure->attributes();
            $type = (string) ($attrs['type'] ?? '');
            $url = (string) ($attrs['url'] ?? '');
            if ($url !== '' && (str_starts_with($type, 'image') || $type === '')) {
                return $url;
            }
        }

        // 2) media:thumbnail / media:content (Media RSS Namespace)
        if (isset($namespaces['media'])) {
            $media = $item->children($namespaces['media']);
            if (isset($media->thumbnail)) {
                $url = (string) $media->thumbnail->attributes()['url'];
                if ($url !== '') {
                    return $url;
                }
            }
            if (isset($media->content)) {
                $url = (string) $media->content->attributes()['url'];
                if ($url !== '') {
                    return $url;
                }
            }
        }

        // 3) Erstes <img> aus dem HTML-Inhalt extrahieren
        return self::extractFirstImageFromHtml($htmlContent);
    }

    private static function extractAtomImage(\SimpleXMLElement $entry, array $namespaces, string $htmlContent): ?string
    {
        if (isset($namespaces['media'])) {
            $media = $entry->children($namespaces['media']);
            if (isset($media->thumbnail)) {
                $url = (string) $media->thumbnail->attributes()['url'];
                if ($url !== '') {
                    return $url;
                }
            }
        }

        return self::extractFirstImageFromHtml($htmlContent);
    }

    private static function extractFirstImageFromHtml(string $html): ?string
    {
        if ($html === '') {
            return null;
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function stripToText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private static function parseDate(string $dateStr): ?int
    {
        if ($dateStr === '') {
            return null;
        }
        $timestamp = strtotime($dateStr);
        return $timestamp !== false ? $timestamp : null;
    }
}
