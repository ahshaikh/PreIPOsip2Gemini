<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return view('welcome');
});

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
