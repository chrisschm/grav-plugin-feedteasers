<?php

namespace Grav\Plugin\FeedTeasers;

/**
 * Minimaler, abhaengigkeitsfreier RSS 2.0 / Atom 1.0 Parser.
 * Nutzt ausschliesslich in PHP eingebaute Erweiterungen (SimpleXML, libxml).
 */
class FeedParser
{
    /**
     * Laedt einen Feed per HTTP und gibt ein normalisiertes Array von Items zurueck.
     *
     * @param string $url
     * @param int    $timeout
     * @throws \RuntimeException wenn der Feed nicht geladen oder geparst werden kann
     * @return array
     */
    public static function fetchAndParse(string $url, int $timeout = 8): array
    {
        $xmlString = self::httpGet($url, $timeout);

        if ($xmlString === null || trim($xmlString) === '') {
            throw new \RuntimeException("Leere Antwort von Feed-URL: {$url}");
        }

        return self::parse($xmlString);
    }

    /**
     * Fuehrt den eigentlichen HTTP-Abruf durch (cURL, falls verfuegbar, sonst
     * stream-basiertes file_get_contents als Fallback).
     */
    private static function httpGet(string $url, int $timeout): ?string
    {
        $userAgent = 'GravFeedTeasersPlugin/1.0 (+https://getgrav.org)';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_USERAGENT      => $userAgent,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $result = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($result === false) {
                throw new \RuntimeException("cURL-Fehler beim Laden von {$url}: {$error}");
            }

            return $result;
        }

        // Fallback ohne cURL
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => $timeout,
                'header'  => "User-Agent: {$userAgent}\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            throw new \RuntimeException("Konnte Feed nicht laden: {$url}");
        }

        return $result;
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
