<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'facebook' => [
        'client_id' => '774972786221082',
        'client_secret' => '77c42cb8c4872c52564edb61320245fa',
        'redirect' => 'https://sitegauge.io/login/facebook/callback',
    ],

    'twitter' => [
        'client_id' => 'zMdlnOUxnSOsqyU2O8FCFqK8z',
        'client_secret' => 'Nb4yO8lGxgx4qvsF0vZuMdadGovUf7lR9iZVB667ErTvZ345kE',
        'redirect' => 'https://sitegauge.io/login/twitter/callback',
    ],

    'google' => [
        'client_id' => '462944543624-1us3o2ijvbl2d7q2o4ln0jaf490ieic6.apps.googleusercontent.com',
        'client_secret' => 'TSjdpfT6I_AuX_GoWkY2g5BG',
        'redirect' => 'https://sitegauge.io/login/google/callback',
    ],


];  
