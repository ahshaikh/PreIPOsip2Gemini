<?php
// V-FINAL-1730-450 (Centralized Rate Limiting Policy Map)
// V-FINAL-1730-470 Centralized Rate Limiting Policy Map with Role Awareness, Plain values only — closures moved to RouteServiceProvider

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Policies (Serializable)
    |--------------------------------------------------------------------------
    | These values are consumed by RouteServiceProvider to register closures.
    | Super-admins bypass limits, admins get higher caps, retail users stricter.
    */

    'login' => [
        'attempts' => 5,   // per minute
    ],

    'api' => [
        'default' => 60,   // per minute
        'admin'   => 120,  // per minute
    ],

    'financial' => [
        'default' => 10,   // per minute
        'admin'   => 30,   // per minute
    ],

    'reports' => [
        'default' => 20,   // per hour
        'admin'   => 100,  // per hour
    ],

    'data-heavy' => [
        'default' => 20,   // per minute
        'admin'   => 50,   // per minute
    ],

    'admin-actions' => [
        'default' => 30,   // per minute
        'admin'   => 60,   // per minute
    ],

];