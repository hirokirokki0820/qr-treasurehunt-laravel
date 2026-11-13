<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    /*
    | kobapp でログイン（kobapp.jp の共通アカウント）
    | redirect_uri を空にすると、このアプリの /host/auth/kobapp/callback を使う。
    | kobapp.jp 側に登録した戻り先と1文字でも違うとログインできない。
    */
    'kobapp' => [
        'url' => env('KOBAPP_URL', 'https://kobapp.jp'),
        'client_id' => env('KOBAPP_CLIENT_ID', 'qr-takarasagashi'),
        'client_secret' => env('KOBAPP_CLIENT_SECRET'),
        'redirect_uri' => env('KOBAPP_REDIRECT_URI'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
