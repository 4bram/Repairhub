<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\OrdenController;
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

    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::put('/servicios/{servicio_id}', [ServicioController::class, 'update'])->whereNumber('servicio_id');
    Route::delete('/servicios/{servicio_id}', [ServicioController::class, 'destroy'])->whereNumber('servicio_id');

    Route::get('/citas', [CitaController::class, 'index']);
    Route::get('/citas/{cita_id}', [CitaController::class, 'show'])->whereNumber('cita_id');
    Route::post('/citas', [CitaController::class, 'store']);
    Route::put('/citas/{cita_id}', [CitaController::class, 'update'])->whereNumber('cita_id');

    Route::post('/ordenes', [OrdenController::class, 'store']);
    Route::put('/ordenes/{orden_id}', [OrdenController::class, 'update'])->whereNumber('orden_id');
});

Route::middleware('membresia:administrador,tecnico')->group(function () {
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/clientes/{cliente_id}', [ClienteController::class, 'show'])->whereNumber('cliente_id');

    Route::get('/equipos', [EquipoController::class, 'index']);
    Route::get('/equipos/{equipo_id}', [EquipoController::class, 'show'])->whereNumber('equipo_id');

    Route::get('/servicios', [ServicioController::class, 'index']);
    Route::get('/ordenes', [OrdenController::class, 'index']);
    Route::get('/ordenes/{orden_id}', [OrdenController::class, 'show'])->whereNumber('orden_id');
    Route::post('/ordenes/{orden_id}/estado', [OrdenController::class, 'cambiarEstado'])->whereNumber('orden_id');
    Route::post('/ordenes/{orden_id}/notas', [OrdenController::class, 'nota'])->whereNumber('orden_id');
    Route::post('/ordenes/{orden_id}/diagnosticos', [OrdenController::class, 'diagnostico'])->whereNumber('orden_id');
});