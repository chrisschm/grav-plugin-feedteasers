<?php

namespace Grav\Plugin\FeedTeasers\Http;

/**
 * Central SSRF protection for the feed fetch in FeedParser::httpGet().
 *
 * The feed URL itself comes "only" from the admin configuration (i.e. from
 * a fundamentally trustworthy person), but that alone isn't enough
 * protection: a harmless external feed entered once can later be
 * compromised or use an HTTP redirect to point to an internal address
 * (loopback, private networks, link-local/cloud metadata such as
 * 169.254.169.254, ...). Without re-checking every redirect target, the
 * check on the initial URL would be rendered pointless.
 *
 * The class therefore deliberately checks not only the starting URL - it
 * must be run again by the caller (FeedParser) for EVERY host actually
 * contacted, including for each individual redirect hop.
 *
 * Analogous to the module of the same name in the Social Linking plugin,
 * without a dependency on it here.
 */
class SsrfGuard
{
    private const ALLOWED_SCHEMES = ['http', 'https'];

    /**
     * @param string[] $allowedPrivateHosts Deliberately allowed
     *        hostnames/IPs (e.g. a self-hosted feed on the internal
     *        network) that should NOT be blocked despite having a
     *        private/local address. Opt-in, empty by default. See
     *        feedteasers.yaml (ssrf_allowed_hosts).
     */
    public function __construct(
        private array $allowedPrivateHosts = []
    ) {
    }

    /**
     * Fully validates a URL (scheme + resolved target IP) and returns the
     * validated IP that the actual connection should be established
     * against (see FeedParser::httpGet() - CURLOPT_RESOLVE pinning against
     * DNS rebinding).
     *
     * @throws \RuntimeException if the URL is rejected
     */
    public function assertAllowedAndResolve(string $url): string
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            throw new \RuntimeException(
                'Nicht erlaubtes URL-Schema "' . $scheme . '" (nur http/https zulässig): ' . $url
            );
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            throw new \RuntimeException('Konnte keinen Host aus der URL lesen: ' . $url);
        }

        // PHP returns IPv6 host literals including square brackets
        // (e.g. "[::1]" for "https://[::1]/..."); filtering/comparison
        // needs the bare address without brackets.
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        if ($this->isExplicitlyAllowed($host)) {
            // Deliberate opt-in (e.g. internal feed) - the host still has
            // to resolve to at least one IP, otherwise the actual request
            // would fail anyway.
            $ip = $this->resolveFirstIp($host);
            if ($ip === null) {
                throw new \RuntimeException('Host "' . $host . '" konnte nicht aufgelöst werden.');
            }
            return $ip;
        }

        // Is the host itself already an IP address (literal)?
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isDisallowedIp($host)) {
                throw new \RuntimeException(
                    'Zugriff auf private/lokale Adresse "' . $host . '" ist nicht erlaubt (SSRF-Schutz).'
                );
            }
            return $host;
        }

        if (strcasecmp($host, 'localhost') === 0 || str_ends_with(strtolower($host), '.localhost')) {
            throw new \RuntimeException('Zugriff auf "' . $host . '" ist nicht erlaubt (SSRF-Schutz).');
        }

        // Hostname: check ALL resolved addresses (A + AAAA), not just the
        // first - a hostname can point to multiple IPs, and DNS is not
        // guaranteed to return a stable order.
        $ips = $this->resolveAllIps($host);
        if (empty($ips)) {
            throw new \RuntimeException('Host "' . $host . '" konnte nicht aufgelöst werden.');
        }

        foreach ($ips as $ip) {
            if ($this->isDisallowedIp($ip)) {
                throw new \RuntimeException(
                    'Host "' . $host . '" löst auf eine private/lokale Adresse auf (' . $ip . ') - '
                    . 'Zugriff aus SSRF-Schutzgründen abgelehnt.'
                );
            }
        }

        // For the later IP pinning (CURLOPT_RESOLVE), a concrete,
        // already-checked address is returned. This guarantees that curl
        // connects to exactly the IP that was checked here, instead of
        // resolving the hostname again at connection time (and possibly
        // getting a different result - keyword DNS rebinding).
        return $ips[0];
    }

    private function isExplicitlyAllowed(string $host): bool
    {
        foreach ($this->allowedPrivateHosts as $allowed) {
            if (strcasecmp(trim((string) $allowed), $host) === 0) {
                return true;
            }
        }
        return false;
    }

    private function resolveFirstIp(string $host): ?string
    {
        $ips = $this->resolveAllIps($host);
        return $ips[0] ?? null;
    }

    /** @return string[] */
    private function resolveAllIps(string $host): array
    {
        $ips = [];

        $ipv4 = gethostbynamel($host);
        if (is_array($ipv4)) {
            $ips = array_merge($ips, $ipv4);
        }

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    if (!empty($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * Checks a single IP address (v4 or v6) against known
     * private/reserved/local ranges that can never be a legitimate target
     * for server-side requests to foreign, public feeds.
     */
    private function isDisallowedIp(string $ip): bool
    {
        // FILTER_FLAG_NO_PRIV_RANGE + FILTER_FLAG_NO_RES_RANGE cover the
        // common private/reserved ranges for v4 AND v6
        // (10/8, 172.16/12, 192.168/16, 127/8, 169.254/16, fc00::/7,
        // fe80::/10, ::1, 0.0.0.0/8, etc.).
        $publicRangeCheck = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($publicRangeCheck === false) {
            return true;
        }

        // Additional special ranges not always covered by the flags
        // above (carrier-grade NAT, benchmarking, IPv4-mapped IPv6,
        // multicast).
        $extraDenylist = [
            '100.64.0.0/10',   // Carrier-Grade NAT (RFC 6598)
            '192.0.0.0/24',    // IETF Protocol Assignments
            '192.0.2.0/24',    // TEST-NET-1
            '198.18.0.0/15',   // Benchmarking
            '198.51.100.0/24', // TEST-NET-2
            '203.0.113.0/24',  // TEST-NET-3
            '224.0.0.0/4',     // Multicast
            '::ffff:0:0/96',   // IPv4-mapped IPv6
        ];

        foreach ($extraDenylist as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $remainderBits = $bits % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($remainderBits === 0) {
            return true;
        }

        $mask = ~(0xFF >> $remainderBits) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }
}
