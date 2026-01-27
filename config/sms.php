<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable SMS notifications globally.
    | When disabled, SMS will not be sent.
    |
    */

    'enabled' => env('SMS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | SMS Provider
    |--------------------------------------------------------------------------
    |
    | Choose your SMS provider: nexmo, twilio, bulk, or default
    | - nexmo: Vonage/Nexmo SMS
    | - twilio: Twilio SMS
    | - bulk: BulkSMS BD (Bangladesh)
    | - default: Log only (development mode)
    |
    */

    'provider' => env('SMS_PROVIDER', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Default Sender ID
    |--------------------------------------------------------------------------
    |
    | The sender name or number that will appear on SMS messages.
    |
    */

    'from' => env('SMS_FROM', 'UCB Bank'),

    /*
    |--------------------------------------------------------------------------
    | Nexmo (Vonage) Configuration
    |--------------------------------------------------------------------------
    |
    | Get your API credentials from: https://dashboard.nexmo.com/
    |
    */

    'nexmo' => [
        'api_key' => env('NEXMO_API_KEY'),
        'api_secret' => env('NEXMO_API_SECRET'),
        'sms_from' => env('NEXMO_SMS_FROM', 'UCB Bank'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    |
    | Get your API credentials from: https://www.twilio.com/console
    |
    */

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM', '+1234567890'),
    ],

    /*
    |--------------------------------------------------------------------------
    | BulkSMS BD Configuration
    |--------------------------------------------------------------------------
    |
    | Get your API credentials from: https://bulksmsbd.net/
    |
    */

    'bulk' => [
        'api_key' => env('BULKSMS_API_KEY'),
        'sender_id' => env('BULKSMS_SENDER_ID', 'UCBBank'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Configuration (for backward compatibility)
    |--------------------------------------------------------------------------
    */

    'api_key' => env('SMS_API_KEY'),
    'api_secret' => env('SMS_API_SECRET'),
    'sender_id' => env('SMS_SENDER_ID'),

];
