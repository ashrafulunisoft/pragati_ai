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
