<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Services\McpSecurityService;
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

        Log::info('[MCP Middleware] Login attempt check', [
            'ip' => $ip,
            'email' => $email,
            'ip_attempts' => $ipAttempts,
            'email_attempts' => $emailAttempts
        ]);

        // Basic threshold trigger (IP: 5+, Email: 3+)
        if ($ipAttempts >= 5 || $emailAttempts >= 3) {

            Log::info('[MCP Middleware] Threshold exceeded, calling AI analysis', [
                'ip' => $ip,
                'email' => $email,
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts
            ]);

            $isMalicious = McpSecurityService::analyzeLogin([
                'ip' => $ip,
                'email' => $email,
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts,
            ]);

            if ($isMalicious) {
                // Block for 1 hour
                Redis::setex("blocked:ip:$ip", 3600, 1);

                Log::warning('[MCP Middleware] IP blocked for malicious activity', [
                    'ip' => $ip,
                    'email' => $email
                ]);

                return response()->json([
                    'error' => 'AI Security System: Malicious activity detected. Access blocked.'
                ], 403);
            }
        }

        return $next($request);
    }
}
