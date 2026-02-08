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

        // Basic threshold trigger (IP: 5+, Email: 3+)
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

            // Log MCP decision to separate security log file
            Log::channel('mcp_security')->info('MCP_DECISION', [
                'ip' => $ip,
                'email' => $email,
                'decision' => $mcp['decision'],
                'attack_type' => $mcp['attack_type'],
                'risk_score' => $mcp['risk_score'],
                'confidence' => $mcp['confidence'],
                'recommended_action' => $mcp['recommended_action'],
            ]);

            if ($mcp['decision'] === 'MALICIOUS') {
                // Block for 1 hour
                Redis::setex("blocked:ip:$ip", 3600, 1);

                // Log warning
                Log::warning('[MCP Middleware] IP blocked for malicious activity', [
                    'ip' => $ip,
                    'email' => $email,
                    'attack_type' => $mcp['attack_type'],
                    'risk_score' => $mcp['risk_score']
                ]);

                // Trigger event for email alert
                event(new McpAttackDetected(
                    [
                        'ip' => $ip,
                        'email' => $email
                    ],
                    $mcp
                ));

                return response()->json([
                    'error' => 'AI Security System: Malicious activity detected. Access blocked.'
                ], 403);
            }
        }

        return $next($request);
    }
}
