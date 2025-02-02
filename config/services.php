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

    'webhook' => [
        'signing_key' => env('WEBHOOK_SIGNING_KEY')
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-southeast-2'),
        'bucket' => env('AWS_BUCKET'),
        'transcription' => [
            'language_code' => env('AWS_TRANSCRIPTION_LANGUAGE', 'en-AU'),
            'media_format' => env('AWS_TRANSCRIPTION_MEDIA', 'wav'),
            'delay' => env('AWS_TRANSCRIPTION_DELAY', 20),
        ],
    ],

    'sns' => [
        'topic_arn' => env('AWS_SNS_TOPIC_ARN'),
    ],

    'clicksend' => [
        'username' => env('CLICKSEND_USERNAME'),
        'api_key' => env('CLICKSEND_API_KEY'),
        'base_url' => env('CLICKSEND_BASE_URL', 'https://rest.clicksend.com/v3'),
        'subaccount_id' => env('CLICKSEND_ACCOUNT_ID'),
    ],

];
