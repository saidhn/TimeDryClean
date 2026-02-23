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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
    ],

    'knet' => [
        'transportal_id' => env('KNET_TRANSPORTAL_ID'),
        'transportal_password' => env('KNET_TRANSPORTAL_PASSWORD'),
        'terminal_resource_key' => env('KNET_TERMINAL_RESOURCE_KEY'),
        'terminal_uri' => env('KNET_TERMINAL_URI', 'https://kpaytest.com.kw/kpg/PaymentHTTP.htm'),
        'currency' => env('KNET_CURRENCY', '414'), // KWD
        'decimals' => env('KNET_DECIMALS', '3'),
        'action' => env('KNET_ACTION', '1'),
        'debug' => env('KNET_DEBUG', true),
    ],
];
