<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\TecnicoController;
use Illuminate\Support\Facades\Route;


Route::middleware('membresia:administrador')->group(function () {
    Route::get('/tecnicos', [TecnicoController::class, 'index']);
    Route::post('/tecnicos', [TecnicoController::class, 'store']);
    Route::delete('/tecnicos/{membresia_id}', [TecnicoController::class, 'destroy'])->whereNumber('membresia_id');

    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::put('/clientes/{cliente_id}', [ClienteController::class, 'update'])->whereNumber('cliente_id');
    Route::delete('/clientes/{cliente_id}', [ClienteController::class, 'destroy'])->whereNumber('cliente_id');

    Route::post('/equipos', [EquipoController::class, 'store']);
    Route::put('/equipos/{equipo_id}', [EquipoController::class, 'update'])->whereNumber('equipo_id');
    Route::delete('/equipos/{equipo_id}', [EquipoController::class, 'destroy'])->whereNumber('equipo_id');
});

Route::middleware('membresia:administrador,tecnico')->group(function () {
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/clientes/{cliente_id}', [ClienteController::class, 'show'])->whereNumber('cliente_id');

    Route::get('/equipos', [EquipoController::class, 'index']);
    Route::get('/equipos/{equipo_id}', [EquipoController::class, 'show'])->whereNumber('equipo_id');
});