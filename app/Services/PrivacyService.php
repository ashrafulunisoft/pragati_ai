<?php

namespace App\Services;

/**
 * Outbound privacy boundary (Problem 6 fix).
 *
 * What leaves the authentication server towards the LLM (direct driver)
 * or the MCP tool (mcp driver):
 *
 *   email -> "email_id:<first 16 hex of HMAC-SHA256(email, salt)>"   (no raw address)
 *   ip    -> "ip_net:<IPv4 /24 | IPv6 /64 prefix>~<8 hex HMAC>"      (coarse network
 *             locality kept as signal; the exact address is not revealed)
 *   counters/time window unchanged (non-identifying numeric behaviour)
 *
 * Raw identifiers are kept ONLY in local audit logs (mcp_security channel)
 * and Redis - they never cross the outbound boundary while pseudonymize=true.
 *
 * The HMAC salt is server-side (MCP_PRIVACY_SALT); the mapping is stable
 * per salt, so the LLM still sees "same identifier as before" for
 * repeat offenders, without learning the identifier itself.
 */
class PrivacyService
{
    public static function email(string $email): string
    {
        if (!self::enabled()) {
            return $email;
        }

        return 'email_id:'.self::hmac(strtolower(trim($email)), 16);
    }

    public static function ip(string $ip): string
    {
        if (!self::enabled()) {
            return $ip;
        }

        return 'ip_net:'.self::networkPrefix($ip).'~'.self::hmac($ip, 8);
    }

    /**
     * Build the outbound context for the LLM / MCP tool from raw login data.
     */
    public static function context(array $data): array
    {
        return [
            'ip'             => self::ip((string) ($data['ip'] ?? '')),
            'email'          => self::email((string) ($data['email'] ?? '')),
            'ip_attempts'    => (int) ($data['ip_attempts'] ?? 0),
            'email_attempts' => (int) ($data['email_attempts'] ?? 0),
        ];
    }

    private static function enabled(): bool
    {
        return (bool) config('services.minimax.pseudonymize', true);
    }

    private static function hmac(string $value, int $length): string
    {
        $salt = (string) config('services.minimax.privacy_salt', '');

        return substr(hash_hmac('sha256', $value, $salt), 0, $length);
    }

    /**
     * IPv4 -> /24 prefix ("203.0.113"), IPv6 -> /64 prefix (first 8 hex of
     * the packed address). Falls back to "unknown" for malformed input.
     */
    private static function networkPrefix(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return implode('.', array_slice(explode('.', $ip), 0, 3));
        }

        $packed = @inet_pton($ip);
        if ($packed !== false && strlen($packed) === 16) {
            return substr(bin2hex($packed), 0, 16); // 64 bits = /64 prefix
        }

        return 'unknown';
    }
}
