<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return view('welcome');
});

// PROTOCOL 1 FIX (2026-01-08): Add named 'login' route to prevent exception handler errors
// - Error: Route [login] not defined
// - Root Cause: Laravel's default exception handler tries to redirect to 'login' route on 401
// - This is an API-only application, so we return JSON instead of redirecting
Route::get('/login', function () {
    return response()->json([
        'message' => 'This is an API-only application. Please use /api/v1/login endpoint.',
        'error' => 'Unauthenticated'
    ], 401);
})->name('login');

// Performance Monitoring Dashboard (protected route - add auth middleware in production)
Route::get('/admin/performance', function () {
    return view('performance-dashboard');
});

// Serve storage files (required for php artisan serve)
// When using php artisan serve, symlinks may not work properly
// This route serves files from storage/app/public directly
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);

    if (!File::exists($filePath)) {
        abort(404);
    }

    return Response::file($filePath);
})->where('path', '.*');
