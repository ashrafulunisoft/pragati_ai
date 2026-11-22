<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'minimax' => [
        'api_key' => env('MINIMAX_API_KEY', env('AI_API_KEY')),
        'host' => env('MINIMAX_API_HOST', 'https://api.minimax.io'),
        'model' => env('MCP_MODEL', env('AI_MODEL', 'MiniMax-M2.1')),
        'provider' => env('MCP_PROVIDER', 'minimax'),

        /*
         * Availability & degraded-security policy (Problem 2 fix)
         *
         * AI-unavailable fallback tiers:
         *   risk >= 80                     -> block (fail-closed)
         *   risk 50-79 (emergency_throttle) -> allow first (degraded_threshold-1) medium-risk
         *                                       analyses per IP, then block for degraded_block_ttl
         *   risk < 50                      -> allow + monitor (logged)
         *
         * Redis-unavailable fallback: counters/blocks are mirrored to the Laravel
         * cache (emergency_* thresholds) so login protection degrades, not dies.
         */
        'timeout' => (int) env('MCP_TIMEOUT', 10),            // seconds per AI HTTP call
        'retries' => (int) env('MCP_RETRIES', 1),             // extra attempts on transient failure
        'degraded_threshold' => (int) env('MCP_DEGRADED_THRESHOLD', 3),
        'degraded_block_ttl' => (int) env('MCP_DEGRADED_BLOCK_TTL', 900),
        'emergency_threshold' => (int) env('MCP_EMERGENCY_THRESHOLD', 10),
        'emergency_block_ttl' => (int) env('MCP_EMERGENCY_BLOCK_TTL', 900),

        /*
         * Configurable detection thresholds (Major-fix M8 - makes the paper's
         * Appendix A configurability claim TRUE). The exact env names the
         * paper documents are honoured, with MCP_* aliases.
         */
        'threshold_ip_analyze' => (int) env('LOGIN_ATTEMPT_THRESHOLD_IP', env('MCP_THRESHOLD_IP_ANALYZE', 5)),
        'threshold_email_analyze' => (int) env('LOGIN_ATTEMPT_THRESHOLD_EMAIL', env('MCP_THRESHOLD_EMAIL_ANALYZE', 3)),
        'window_seconds' => (int) env('ATTEMPT_WINDOW_SECONDS', env('MCP_WINDOW_SECONDS', 900)),
        'block_ttl' => (int) env('BLOCK_DURATION_SECONDS', env('MCP_BLOCK_TTL', 3600)),

        // Risk-score bands (decision + recommended_action)
        'risk_band_malicious' => (int) env('MCP_RISK_BAND_MALICIOUS', 80),
        'risk_band_suspicious' => (int) env('MCP_RISK_BAND_SUSPICIOUS', 50),
        'risk_band_captcha' => (int) env('MCP_RISK_BAND_CAPTCHA', 30),

        // Rule weights: [[min_attempts, points], ...] - first matching band wins
        'rule_weights' => [
            'ip'    => [[10, 50], [5, 30], [3, 15]],
            'email' => [[5, 40], [3, 25]],
        ],

        /*
         * Shared-NAT mitigation (Major-fix M9): when at least this many
         * DISTINCT accounts have logged in successfully from one IP within
         * the window, the IP is treated as shared and blocks are scoped to
         * the attacking ACCOUNT instead of blocking everyone behind the NAT.
         */
        'shared_ip_success_threshold' => (int) env('MCP_SHARED_IP_SUCCESS_THRESHOLD', 3),
        'shared_ip_max_blocked_accounts' => (int) env('MCP_SHARED_IP_MAX_BLOCKED_ACCOUNTS', 5),
        'shared_ip_max_ip_attempts' => (int) env('MCP_SHARED_IP_MAX_IP_ATTEMPTS', 25),

        /*
         * CAPTCHA/OTP step-up enforcement (Major-fix M10). When the fused
         * verdict is SUSPICIOUS (not MALICIOUS) the recommended action is now
         * ENFORCED: the login is answered with HTTP 428 + a single-use
         * challenge the client must solve (math captcha or emailed OTP).
         */
        'challenge_enabled' => (bool) env('MCP_CHALLENGE_ENABLED', true),
        'challenge_ttl' => (int) env('MCP_CHALLENGE_TTL', 300),
        'challenge_max_attempts' => (int) env('MCP_CHALLENGE_MAX_ATTEMPTS', 3),
        'challenge_max_per_ip' => (int) env('MCP_CHALLENGE_MAX_PER_IP', 10),
        'otp_delivery' => env('MCP_OTP_DELIVERY', 'log'),   // log | mail

        /*
         * MCP integration (Problem 3 fix)
         *
         * driver = 'direct' : McpSecurityService -> MiniMaxClient (plain LLM API call)
         * driver = 'mcp'    : McpSecurityService -> McpClient (JSON-RPC 2.0, Streamable
         *                     HTTP, protocol 2025-06-18) -> /api/mcp -> analyze_login_risk tool
         */
        'driver' => env('MCP_DRIVER', 'direct'),
        // Chat-completions path override. Empty = auto-detect: MiniMax-style
        // hosts get /v1/chat/completions; OpenAI-compatible hosts that already
        // carry a version prefix (Gemini ".../v1beta/openai") get /chat/completions
        // (appending /v1 there produced a 404 on every live call).
        'chat_path' => env('MCP_CHAT_PATH', ''),
        'mcp_server_url' => env('MCP_SERVER_URL', null),          // defaults to url('/api/mcp')
        'mcp_server_token' => env('MCP_SERVER_TOKEN', ''),
        'mcp_protocol_version' => env('MCP_PROTOCOL_VERSION', '2025-06-18'),
        'mcp_handshake' => env('MCP_HANDSHAKE', true),

        /*
         * Privacy (Problem 6 fix): pseudonymise identifiers before any
         * context leaves the server (email -> salted HMAC, IP -> network
         * prefix + salted HMAC). Set MCP_PRIVACY_PSEUDONYMIZE=false only
         * for local experiments - never in production.
         */
        'pseudonymize' => (bool) env('MCP_PRIVACY_PSEUDONYMIZE', true),
        'privacy_salt' => env('MCP_PRIVACY_SALT', ''),
    ],

//    'minimax' => [
//     'api_key' => env('MCP_PROVIDER') === 'glm' 
//         ? env('GLM_API_KEY')
//         : env('MINIMAX_API_KEY'),
//     'host' => env('MCP_PROVIDER') === 'glm'
//         ? env('GLM_API_HOST')
//         : env('MINIMAX_API_HOST'),
//     'model' => env('MCP_MODEL', 'MiniMax-M2.1'),
//     'provider' => env('MCP_PROVIDER', 'minimax'),
// ],

];
