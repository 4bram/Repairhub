<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth
Route::get('/auth/google', [
    OAuthController::class,
    'redirigirGoogle'
])->name('google.redirect');

Route::get('/auth/google/callback', [
    OAuthController::class,
    'callbackGoogle'
])->name('google.callback');
