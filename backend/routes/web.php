<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Performance Monitoring Dashboard (protected route - add auth middleware in production)
Route::get('/admin/performance', function () {
    return view('performance-dashboard');
});
