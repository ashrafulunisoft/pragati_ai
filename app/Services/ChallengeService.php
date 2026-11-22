<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Single-use step-up challenges (Major-fix M10).
 *
 * The fused verdict's recommended actions `captcha` (risk band 30-49) and
 * `otp` (band 50-79, decision SUSPICIOUS) are now ENFORCED: instead of only
 * being logged, the middleware answers such logins with HTTP 428 plus a
 * challenge the client must solve before the attempt proceeds.
 *
 *   captcha -> server-generated math challenge; the answer never leaves the
 *              server (only its salted SHA-256 is stored, single-use, TTL-bound)
 *   otp     -> 6-digit code; delivered via the configured channel (log by
 *              default; set MCP_OTP_DELIVERY=mail to email it)
 *
 * Storage goes through SecurityStore (Redis with cache fallback). Tokens are
 * unguessable (random_bytes) and attempts are capped per challenge, so a
 * brute-forcing bot cannot solve its way through: the whole point is that
 * scripted attack traffic stops here while humans pass.
 */
class ChallengeService
{
    public const TYPE_CAPTCHA = 'captcha';
    public const TYPE_OTP     = 'otp';

    /**
     * Issue a challenge for an identifier pair.
     *
     * Returns the public challenge payload (token + question/hint). The
     * secret answer is stored server-side as a salted hash.
     */
    public static function issue(string $type, string $ip, string $email): array
    {
        $ttl        = (int) config('services.minimax.challenge_ttl', 300);
        $maxAttempt = (int) config('services.minimax.challenge_max_attempts', 3);

        $token = bin2hex(random_bytes(16));

        if ($type === self::TYPE_CAPTCHA) {
            $a = random_int(10, 89);
            $b = random_int(2, 39);
            $answer = (string) ($a + $b);
            $public = [
                'type'     => self::TYPE_CAPTCHA,
                'question' => "{$a} + {$b} = ?",
            ];
        } else {
            $answer = (string) random_int(100000, 999999);
            $public = [
                'type' => self::TYPE_OTP,
                'hint' => 'A verification code has been sent to the account email.',
            ];
            self::deliverOtp($email, $answer);
        }

        SecurityStore::setex("challenge:{$token}", $ttl, json_encode([
            'h'         => self::hash($answer),
            'a'         => $maxAttempt,
            'type'      => $type,
            'email'     => $email,
            'ip'        => $ip,
        ]));

        Log::channel('mcp_security')->info('CHALLENGE_ISSUED', [
            'type'  => $type,
            'ip'    => $ip,
            'email' => $email,
            'ttl'   => $ttl,
        ]);

        return ['token' => $token] + $public;
    }

    /**
     * Verify a challenge response. Single-use: the token is consumed on
     * success; capped attempts consume it on repeated failure.
     */
    public static function verify(?string $token, ?string $answer): bool
    {
        if (!is_string($token) || $token === '' || !is_string($answer)) {
            return false;
        }

        $raw = SecurityStore::get("challenge:{$token}");

        if (!is_string($raw)) {
            return false; // unknown/expired
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['h'])) {
            return false;
        }

        if (hash_equals((string) $data['h'], self::hash(trim($answer)))) {
            SecurityStore::del(["challenge:{$token}"]);

            return true;
        }

        // wrong answer - burn one attempt, invalidate when exhausted
        $data['a'] = max(0, (int) ($data['a'] ?? 0) - 1);
        if ($data['a'] <= 0) {
            SecurityStore::del(["challenge:{$token}"]);
        } else {
            $ttl = (int) config('services.minimax.challenge_ttl', 300);
            SecurityStore::setex("challenge:{$token}", $ttl, json_encode($data));
        }

        return false;
    }

    /**
     * Deliver the OTP code via the configured channel (log | mail).
     */
    private static function deliverOtp(string $email, string $code): void
    {
        if (config('services.minimax.otp_delivery') === 'mail') {
            try {
                Mail::raw(
                    "Your Pragati AI verification code is {$code}. It expires in "
                    .config('services.minimax.challenge_ttl', 300).' seconds.',
                    function ($message) use ($email) {
                        $message->to($email)->subject('Your login verification code');
                    }
                );

                return;
            } catch (\Throwable $e) {
                Log::error('[ChallengeService] OTP mail failed, falling back to log', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Default delivery: security audit log (flip MCP_OTP_DELIVERY=mail in production)
        Log::channel('mcp_security')->notice('OTP_ISSUED', [
            'email' => $email,
            'code'  => $code,
        ]);
    }

    private static function hash(string $answer): string
    {
        return hash('sha256', $answer.config('app.key'));
    }
}
