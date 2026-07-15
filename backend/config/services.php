<?php

return [

    'douyin_rebate' => [
        'mock' => env('DOUYIN_REBATE_MOCK', true),
        'endpoint' => env('DOUYIN_API_ENDPOINT', 'https://openapi-fxg.jinritemai.com'),
        'app_key' => env('DOUYIN_APP_KEY'),
        'app_secret' => env('DOUYIN_APP_SECRET'),
        'access_token' => env('DOUYIN_ACCESS_TOKEN'),
        'pid' => env('DOUYIN_PID'),
        'timeout' => (int) env('DOUYIN_API_TIMEOUT', 10),
    ],

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

];
