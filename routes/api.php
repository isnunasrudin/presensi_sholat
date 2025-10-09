<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PrayerRecordController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RombonganBelajarController;
use App\Http\Controllers\Api\UserImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    
    // Impersonation routes (Admin only)
    Route::post('/impersonate/{userId}', [AuthController::class, 'impersonate']);
    Route::post('/stop-impersonating', [AuthController::class, 'stopImpersonating']);

    // Prayer record routes
    Route::apiResource('prayer-records', PrayerRecordController::class);
    Route::get('/prayer-records-statistics', [PrayerRecordController::class, 'statistics']);
    Route::get('/prayer-records-user-recap', [PrayerRecordController::class, 'userRecap']);

    // User management routes (admin only for most operations)
    Route::apiResource('users', UserController::class);
    
    // User import routes (admin only)
    Route::post('/users/import', [UserImportController::class, 'import']);
    Route::get('/users/import/template', [UserImportController::class, 'downloadTemplate']);

    // Rombongan Belajar management routes (admin only)
    Route::apiResource('rombongan-belajar', RombonganBelajarController::class);
    Route::get('/rombongan-belajar-tahun-angkatan', [RombonganBelajarController::class, 'getTahunAngkatan']);
    Route::post('/rombongan-belajar/promote/{tahunAngkatan}', [RombonganBelajarController::class, 'promote']);
    Route::post('/rombongan-belajar/graduate/{tingkat}', [RombonganBelajarController::class, 'graduate']);
});
