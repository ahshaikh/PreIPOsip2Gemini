<?php

// V-FINAL-1730-142 (Corrected)

return [

    /*
    |--------------------------------------------------------------------------
    | CORS (Cross-Origin Resource Sharing) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines which cross-origin operations may execute
    | in web browsers.
    |
    */

    // FIX: Added 'storage/*' to allow cross-origin image requests from frontend
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    // --- THIS IS THE FIX ---
    // We are explicitly telling it to trust your frontend URL
    // instead of the default '*'
    // 'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    
    // for development only:
    'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // --- THIS IS THE OTHER FIX ---
    // We are allowing it to handle credentials (cookies)
    'supports_credentials' => true,

];