<?php
// V-SECURITY-ENHANCED - Token Expiration and Security Settings

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1,%s',
        env('APP_URL') ? parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    | SECURITY FIX: Set token expiration to 60 minutes
    */

    'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 60),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Expiration
    |--------------------------------------------------------------------------
    | Custom: Refresh tokens expire after 7 days
    */

    'refresh_expiration' => env('SANCTUM_REFRESH_EXPIRATION', 60 * 24 * 7), // 7 days

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'preipo_'),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
