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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transportadora (API de tarifas de envío)
    |--------------------------------------------------------------------------
    |
    | El enunciado apunta a POST https://tarifas.transportadora.test/v1/cotizar,
    | pero ese dominio no existe y la credencial "se entrega por solicitud".
    | Aquí quedan parametrizados la URL y la clave para poder cambiarlos sin
    | tocar el código de la aplicación.
    |
    */

    'transportadora' => [
        'base_url' => env('TARIFAS_BASE_URL', 'http://tarifas:8090'),
        'key' => env('TARIFAS_API_KEY'),
        'timeout' => (int) env('TARIFAS_TIMEOUT', 5),
        'reintentos' => (int) env('TARIFAS_REINTENTOS', 2),
    ],

];
