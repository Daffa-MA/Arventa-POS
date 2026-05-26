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

    'arventa_developer' => [
        'name' => env('ARVENTA_DEVELOPER_NAME', 'Arventa Developer'),
        'username' => env('ARVENTA_DEVELOPER_USERNAME'),
        'email' => env('ARVENTA_DEVELOPER_EMAIL'),
        'password' => env('ARVENTA_DEVELOPER_PASSWORD'),
    ],

    'arventa_deployment' => [
        'mode' => env('ARVENTA_DEPLOYMENT_MODE', 'manual'),
        'pos_base_domain' => env('ARVENTA_POS_BASE_DOMAIN'),
        'app_public_host' => env('ARVENTA_APP_PUBLIC_HOST', parse_url((string) env('APP_URL', ''), PHP_URL_HOST)),
        'dns' => [
            'provider' => env('ARVENTA_DNS_PROVIDER', 'none'),
            'record_type' => env('ARVENTA_DNS_RECORD_TYPE', 'CNAME'),
            'record_content' => env('ARVENTA_DNS_RECORD_CONTENT', env('ARVENTA_APP_PUBLIC_HOST', parse_url((string) env('APP_URL', ''), PHP_URL_HOST))),
            'ttl' => (int) env('ARVENTA_DNS_TTL', 1),
            'proxied' => filter_var(env('ARVENTA_DNS_PROXIED', false), FILTER_VALIDATE_BOOL),
        ],
        'cloudflare' => [
            'token' => env('CLOUDFLARE_API_TOKEN'),
            'zone_id' => env('CLOUDFLARE_ZONE_ID'),
            'zone_domain' => env('CLOUDFLARE_ZONE_DOMAIN', env('ARVENTA_POS_BASE_DOMAIN')),
        ],
        'caprover' => [
            'enabled' => filter_var(env('CAPROVER_AUTOMATION_ENABLED', false), FILTER_VALIDATE_BOOL),
            'base_url' => env('CAPROVER_BASE_URL'),
            'password' => env('CAPROVER_PASSWORD'),
            'auth_token' => env('CAPROVER_AUTH_TOKEN'),
            'namespace' => env('CAPROVER_NAMESPACE', 'captain'),
            'app_name' => env('CAPROVER_APP_NAME', 'arventa'),
            'enable_ssl' => filter_var(env('CAPROVER_ENABLE_SSL', true), FILTER_VALIDATE_BOOL),
        ],
    ],

];
