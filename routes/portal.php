<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PortalClienteController;

Route::middleware('auth:sanctum')
    ->prefix('portal')
    ->group(function () {

        // Listar órdenes propias.
        Route::get(
            '/ordenes',
            [PortalClienteController::class, 'index']
        );

        // Consultar una orden propia.
        Route::get(
            '/ordenes/{orden_id}',
            [PortalClienteController::class, 'show']
        )->whereNumber('orden_id');

        // Historial visible de la orden.
        Route::get(
            '/ordenes/{orden_id}/seguimientos',
            [PortalClienteController::class, 'seguimientos']
        )->whereNumber('orden_id');

        // Evidencias visibles.
        Route::get(
            '/ordenes/{orden_id}/evidencias',
            [PortalClienteController::class, 'evidencias']
        )->whereNumber('orden_id');

        // Descargar una evidencia autorizada.
        Route::get(
            '/ordenes/{orden_id}/evidencias/{evidencia_id}/archivo',
            [PortalClienteController::class, 'archivo']
        )->whereNumber([
            'orden_id',
            'evidencia_id',
        ]);

    });