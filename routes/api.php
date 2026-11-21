<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EstablecimientoController;
use App\Http\Controllers\OAuthController;

Route::post(
    '/auth/google/exchange',
    [OAuthController::class, 'intercambiarCodigo']
)->middleware('throttle:10,1');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', fn () => response()->json(['ok' => true]));

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/registro', [AuthController::class, 'registro'])->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/establecimientos', [EstablecimientoController::class, 'index']);
    Route::post('/establecimientos', [EstablecimientoController::class, 'store']);

    Route::prefix('establecimientos/{establecimiento_id}')
    ->where(['establecimiento_id' => '[0-9]+'])
    ->group(function () {
        Route::get('/', [EstablecimientoController::class, 'show'])->middleware('membresia');
        Route::put('/', [EstablecimientoController::class, 'update'])->middleware('membresia:administrador');
    });
});