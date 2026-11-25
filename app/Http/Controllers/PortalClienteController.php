<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\Evidencia;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PortalClienteController extends Controller
{
    /**
     * Obtener exclusivamente las órdenes que pertenecen
     * al usuario autenticado.
     */
    private function ordenesDelCliente(Request $request): Builder
    {
        $usuarioId = $request->user()->id;

        return Orden::query()
            ->whereHas('cliente', function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId)
                    ->whereColumn(
                        'clientes.establecimiento_id',
                        'ordenes_servicio.establecimiento_id'
                    );
            });
    }

    /**
     * Buscar una orden y comprobar su propiedad.
     */
    private function ordenVisible(Request $request): Orden
    {
        return $this->ordenesDelCliente($request)
            ->findOrFail($request->route('orden_id'));
    }

    /**
     * Buscar una evidencia visible de una orden autorizada.
     */
    private function evidenciaVisible(
        Request $request,
        Orden $orden
    ): Evidencia {
        return $orden->evidenciasVisibles()
            ->findOrFail($request->route('evidencia_id'));
    }

    /**
     * Seleccionar únicamente los datos de la orden
     * que pueden mostrarse al cliente.
     */
    private function datosOrden(Orden $orden): array
    {
        return [
            'id' => $orden->id,
            'folio' => $orden->folio,
            'estado' => $orden->estado,
            'problema_reportado' => $orden->problema_reportado,
            'fecha_ingreso' => $orden->fecha_ingreso,
            'fecha_estimada' => $orden->fecha_estimada,
            'fecha_entrega' => $orden->fecha_entrega,
            'establecimiento_id' => $orden->establecimiento_id,
            'equipo_id' => $orden->equipo_id,
        ];
    }

    /**
     * Seleccionar los datos públicos de un seguimiento.
     */
    private function datosSeguimiento($seguimiento): array
    {
        return [
            'id' => $seguimiento->id,
            'estado' => $seguimiento->estado,
            'descripcion' => $seguimiento->descripcion,
            'creado_en' => $seguimiento->creado_en,
        ];
    }

    /**
     * Seleccionar los datos públicos de una evidencia.
     */
    private function datosEvidencia(Evidencia $evidencia): array
    {
        return [
            'id' => $evidencia->id,
            'seguimiento_id' => $evidencia->seguimiento_id,
            'tipo_mime' => $evidencia->tipo_mime,
            'tamano_bytes' => $evidencia->tamano_bytes,
            'descripcion' => $evidencia->descripcion,
            'creado_en' => $evidencia->creado_en,

            'url_archivo' => '/api/portal/ordenes/'
                . $evidencia->orden_id
                . '/evidencias/'
                . $evidencia->id
                . '/archivo',
        ];
    }

    /**
     * GET /api/portal/ordenes
     *
     * Listar las órdenes del cliente autenticado.
     */
    public function index(Request $request)
    {
        $ordenes = $this->ordenesDelCliente($request)
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->paginate(20);

        $ordenes->through(function (Orden $orden) {
            return $this->datosOrden($orden);
        });

        return response()->json($ordenes);
    }

    /**
     * GET /api/portal/ordenes/{orden_id}
     *
     * Consultar los detalles de una orden propia.
     */
    public function show(Request $request)
    {
        $orden = $this->ordenVisible($request);

        $datos = $this->datosOrden($orden);

        $datos['total_seguimientos'] = $orden
            ->seguimientosVisibles()
            ->count();

        $datos['total_evidencias'] = $orden
            ->evidenciasVisibles()
            ->count();

        return response()->json($datos);
    }

    /**
     * GET /api/portal/ordenes/{orden_id}/seguimientos
     *
     * Consultar exclusivamente los seguimientos públicos.
     */
    public function seguimientos(Request $request)
    {
        $orden = $this->ordenVisible($request);

        $seguimientos = $orden->seguimientosVisibles()
            ->orderBy('creado_en')
            ->orderBy('id')
            ->get()
            ->map(function ($seguimiento) {
                return $this->datosSeguimiento($seguimiento);
            });

        return response()->json($seguimientos);
    }

    /**
     * GET /api/portal/ordenes/{orden_id}/evidencias
     *
     * Consultar exclusivamente las evidencias autorizadas.
     */
    public function evidencias(Request $request)
    {
        $orden = $this->ordenVisible($request);

        $evidencias = $orden->evidenciasVisibles()
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->get()
            ->map(function (Evidencia $evidencia) {
                return $this->datosEvidencia($evidencia);
            });

        return response()->json($evidencias);
    }

    /**
     * GET /api/portal/ordenes/{orden_id}/
     * evidencias/{evidencia_id}/archivo
     *
     * Descargar una evidencia autorizada.
     */
    public function archivo(Request $request)
    {
        $orden = $this->ordenVisible($request);

        $evidencia = $this->evidenciaVisible(
            $request,
            $orden
        );

        $disk = Storage::disk('evidencias');

        try {
            $existe = $disk->exists(
                $evidencia->ruta_archivo
            );

            if (! $existe) {
                return response()->json([
                    'message' => 'Archivo no encontrado',
                ], 404);
            }

            return $disk->download(
                $evidencia->ruta_archivo,
                basename($evidencia->ruta_archivo),
                [
                    'Content-Type' => $evidencia->tipo_mime,
                    'Cache-Control' => 'private, no-store',
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo recuperar el archivo',
            ], 503);
        }
    }
}