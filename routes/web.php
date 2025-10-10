<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicUserController;

// Public route for NFC user profile
Route::get('/u/{user}', [PublicUserController::class, 'show'])->name('public.user.show');

// Add login route for authentication middleware
Route::get('/login', function () {
    return view('app');
})->name('login');

// Catch-all for SPA
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!u\/).*$');
