<?php
// V-AUDIT-MODULE6-001 (Created) - SSRF Protection Helper
// Purpose: Validate URLs to prevent Server-Side Request Forgery attacks

namespace App\Helpers;

/**
 * UrlValidator - SSRF Protection Helper
 *
 * Provides validation methods to prevent Server-Side Request Forgery (SSRF) attacks
 * by ensuring URLs don't resolve to private/internal IP addresses or restricted protocols.
 *
 * SSRF Attack Example:
 * An attacker sets price_api_endpoint to "http://169.254.169.254/latest/meta-data/"
 * (AWS metadata endpoint) to steal cloud credentials.
 *
 * This helper prevents such attacks by:
 * 1. Validating URL format
 * 2. Requiring HTTPS protocol
 * 3. Blocking private/reserved IP ranges
 * 4. Blocking localhost and internal addresses
 */
class UrlValidator
{
    /**
     * Private IP ranges that should be blocked
     *
     * @var array
     */
    private static $privateRanges = [
        '10.0.0.0/8',       // Private network
        '172.16.0.0/12',    // Private network
        '192.168.0.0/16',   // Private network
        '127.0.0.0/8',      // Loopback
        '169.254.0.0/16',   // Link-local (AWS metadata)
        '0.0.0.0/8',        // Current network
        '224.0.0.0/4',      // Multicast
        '240.0.0.0/4',      // Reserved
        'fc00::/7',         // IPv6 Unique local
        'fe80::/10',        // IPv6 Link-local
        '::1/128',          // IPv6 Loopback
    ];

    /**
     * Validate URL for SSRF protection
     *
     * @param string $url The URL to validate
     * @param bool $requireHttps Whether to require HTTPS (default: true)
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validateUrl(string $url, bool $requireHttps = true): array
    {
        // 1. Check if URL is well-formed
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }

        // 2. Parse URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return ['valid' => false, 'error' => 'Unable to parse URL'];
        }

        // 3. Check protocol
        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https'])) {
            return ['valid' => false, 'error' => 'Only HTTP(S) protocols are allowed'];
        }

        if ($requireHttps && $scheme !== 'https') {
            return ['valid' => false, 'error' => 'HTTPS is required for security'];
        }

        // 4. Check for localhost/internal hostnames
        $host = strtolower($parsed['host']);
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '[::1]', '::1'];
        if (in_array($host, $blockedHosts)) {
            return ['valid' => false, 'error' => 'Localhost and loopback addresses are blocked'];
        }

        // 5. Resolve hostname to IP and check if it's in private range
        $ip = gethostbyname($host);
        if ($ip === $host) {
            // gethostbyname returns the hostname if resolution fails
            // This might be okay (hostname only), but we'll allow it for now
            // In strict mode, we could block this
        } else {
            // Check if resolved IP is in private range
            if (self::isPrivateIp($ip)) {
                return ['valid' => false, 'error' => 'URL resolves to a private/internal IP address'];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Check if an IP address is in a private/reserved range
     *
     * @param string $ip IP address to check
     * @return bool True if private, false if public
     */
    private static function isPrivateIp(string $ip): bool
    {
        // Quick checks for common private IPs
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }

    /**
     * Validate and sanitize URL for external API calls
     *
     * @param string|null $url URL to validate
     * @param bool $requireHttps Whether HTTPS is required
     * @return string|null Sanitized URL or null if invalid
     * @throws \InvalidArgumentException if URL is invalid
     */
    public static function validateOrFail(?string $url, bool $requireHttps = true): ?string
    {
        if (empty($url)) {
            return null;
        }

        $result = self::validateUrl($url, $requireHttps);

        if (!$result['valid']) {
            throw new \InvalidArgumentException("Invalid URL: {$result['error']}");
        }

        return $url;
    }
}
