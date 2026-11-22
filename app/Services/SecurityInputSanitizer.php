<?php

namespace App\Services;

/**
 * Sanitises untrusted login-request inputs before they are used in Redis keys
 * or LLM prompts (Problem 4 fix).
 *
 * Defence layers:
 *  1. Control characters (newlines, NUL, ...) are stripped so an attacker
 *     cannot forge multi-line prompt structures or delimiters.
 *  2. Strict format validation (FILTER_VALIDATE_EMAIL / FILTER_VALIDATE_IP) -
 *     angle brackets, quotes-with-spaces, multi-@ payloads etc. are rejected,
 *     which makes "</context>" forging inside an identifier impossible.
 *  3. Length caps.
 *
 * Invalid values return null: callers skip AI analysis and let the controller
 * validation reject the request outright.
 */
class SecurityInputSanitizer
{
    public const MAX_EMAIL_LENGTH = 254;

    /**
     * Returns a normalised, valid email (lowercase, control chars stripped),
     * or null when the value is not a valid email address.
     */
    public static function email(?string $raw): ?string
    {
        $value = self::stripControl((string) $raw);

        if ($value === '' || mb_strlen($value) > self::MAX_EMAIL_LENGTH) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? strtolower($value) : null;
    }

    /**
     * Returns a valid IP (v4/v6) with control chars stripped, or null.
     */
    public static function ip(?string $raw): ?string
    {
        $value = self::stripControl((string) $raw);

        if ($value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_IP) ?: null;
    }

    /**
     * Remove control characters (0x00-0x1F, 0x7F) incl. CR/LF/TAB.
     */
    public static function stripControl(string $value): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', trim($value));
    }
}
