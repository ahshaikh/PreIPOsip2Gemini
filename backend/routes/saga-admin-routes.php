<?php

use App\Http\Controllers\Api\Admin\SagaManagementController;
use Illuminate\Support\Facades\Route;

/**
 * Saga Management Routes
 *
 * Add these to your main routes/api.php file:
 *
 * Route::middleware(['auth:sanctum', 'role:admin', 'permission:system.manage'])
 *     ->prefix('admin/sagas')
 *     ->group(base_path('routes/saga-admin-routes.php'));
 */

// Dashboard stats
Route::get('stats', [SagaManagementController::class, 'stats']);

// List sagas with filtering
Route::get('', [SagaManagementController::class, 'index']);

// View specific saga
Route::get('{saga}', [SagaManagementController::class, 'show']);

// Saga actions
Route::post('{saga}/retry', [SagaManagementController::class, 'retry']);
Route::post('{saga}/resolve', [SagaManagementController::class, 'resolve']);
Route::post('{saga}/force-compensate', [SagaManagementController::class, 'forceCompensate']);
Route::get('{saga}/payment', [SagaManagementController::class, 'getPaymentDetails']);

// Recovery
Route::post('recovery/run', [SagaManagementController::class, 'runRecovery']);
