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

    'google_cloud' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'key_file' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
    ],

    'region'  => config('services.s3.region'),
    'credentials' => [
        'key'    => config('services.s3.key'),
        'secret' => config('services.s3.secret'),
    ],

    'openAiKey' => env('OPEN_AI_KEY'),
    'claudeAiKey' => env('CLAUDE_AI_KEY'),
    'cuiBusiness' => env('CUI_BUSSINESS'),
    'spvApiUrlCustom' => env('SPV_API_URL_CUSTOM'),

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
