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
    'paginate'=>[
        'value'=>env('PAGINATE')
    ],
    'messages_custom'=>[
        'message_custom_success'=>env('MESSAGE_CUSTOM_SUCCESS'),
        'message_custom_success_update'=>env('MESSAGE_CUSTOM_SUCCESS_UPDATE'),
        'message_custom_success_delete'=>env('MESSAGE_CUSTOM_SUCCESS_DELETE'),
        'message_custom_error'=>env('MESSAGE_CUSTOM_ERROR'),
        'message_custom_resource_not_available'=>env('MESSAGE_CUSTOM_RESOURCE_NOT_AVAILABLE')
    ]

];
