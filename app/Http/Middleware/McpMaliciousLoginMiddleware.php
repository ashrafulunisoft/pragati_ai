<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Services\McpSecurityService;
use App\Events\McpAttackDetected;
use Symfony\Component\HttpFoundation\Response;

class McpMaliciousLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip    = $request->ip();
        $email = $request->input('email');

        // Skip if no email provided (not a login attempt)
        if (empty($email)) {
            return $next($request);
        }

        $ipKey    = "attack:login:ip:$ip";
        $emailKey = "attack:login:email:$email";

        $ipAttempts    = Redis::get($ipKey) ?? 0;
        $emailAttempts = Redis::get($emailKey) ?? 0;

        // Only use MCP AI analysis when login limits are exceeded (5+ IP attempts or 3+ email attempts)
        // This saves API calls and improves performance
        if ($ipAttempts >= 5 || $emailAttempts >= 3) {
            Log::info('[MCP Middleware] Threshold exceeded, calling AI analysis', [
                'ip' => $ip,
                'email' => $email,
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts
            ]);

            // Get full MCP response with decision, attack_type, risk_score, confidence, recommended_action
            $mcp = McpSecurityService::analyzeLogin([
                'ip' => $ip,
                'email' => $email,
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts,
            ]);

            // Note: MCP_DECISION is already logged by McpSecurityService::analyzeLogin()
            // No need to log again here to avoid duplicates

            // Block if:
            // 1. Decision is MALICIOUS, OR
            // 2. Risk score is HIGH (>= 80)
            $shouldBlock = false;
            $blockReason = '';

            if ($mcp['decision'] === 'MALICIOUS') {
                $shouldBlock = true;
                $blockReason = 'MALICIOUS decision detected';
            } elseif ($mcp['risk_score'] >= 80) {
                $shouldBlock = true;
                $blockReason = 'High risk score (' . $mcp['risk_score'] . ') detected';
            }

            if ($shouldBlock) {
                // Block for 1 hour
                Redis::setex("blocked:ip:$ip", 3600, 1);

                // Log warning
                Log::warning('[MCP Middleware] IP blocked - ' . $blockReason, [
                    'ip' => $ip,
                    'email' => $email,
                    'attack_type' => $mcp['attack_type'],
                    'risk_score' => $mcp['risk_score'],
                    'decision' => $mcp['decision'],
                    'ip_attempts' => $ipAttempts,
                    'email_attempts' => $emailAttempts
                ]);

                // Trigger event for email alert (wrapped in try-catch to prevent email errors from blocking response)
                try {
                    event(new McpAttackDetected(
                        [
                            'ip' => $ip,
                            'email' => $email
                        ],
                        $mcp
                    ));
                } catch (\Exception $e) {
                    // Log email error but don't block response
                    Log::error('[MCP Middleware] Failed to send security alert email', [
                        'error' => $e->getMessage(),
                        'ip' => $ip,
                        'email' => $email
                    ]);
                }

                return response()->json([
                    'error' => 'AI Security System: ' . $blockReason . '. Access blocked for 1 hour.'
                ], 403);
            }
        }

        return $next($request);
    }
}
