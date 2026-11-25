<?php

namespace App\Http\Controllers;

use App\Models\Evidencia;
use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class EvidenciaController extends Controller
{
    private const CERRADAS = ['entregado', 'cancelado'];

    private function membresia(Request $request)
    {
        return $request->attributes->get('membresia');
    }

    // Administrador: órdenes de su establecimiento.
    // Técnico: únicamente las órdenes asignadas.
    private function ordenVisible(Request $request): Orden
    {
        $m = $this->membresia($request);

        $q = Orden::where(
            'establecimiento_id',
            $m->establecimiento_id
        );

        if ($m->rol === 'tecnico') {
            $q->where('tecnico_membresia_id', $m->id);
        }

        return $q->findOrFail(
            $request->route('orden_id')
        );
    }

    private function evidenciaVisible(
        Request $request,
        Orden $orden
    ): Evidencia {
        return $orden->evidencias()->findOrFail(
            $request->route('evidencia_id')
        );
    }

    // Listar evidencias de una orden.
    public function index(Request $request)
    {
        $orden = $this->ordenVisible($request);

        return response()->json(
            $orden->evidencias()
                ->orderByDesc('creado_en')
                ->get()
        );
    }

    // Subir una fotografía.
    public function store(Request $request)
    {
        $orden = $this->ordenVisible($request);

        if (in_array($orden->estado, self::CERRADAS, true)) {
            return response()->json([
                'message' => 'La orden está cerrada',
            ], 422);
        }

        $datos = $request->validate([
            'archivo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'seguimiento_id' => [
                'nullable',
                'integer',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'visible_cliente' => [
                'sometimes',
                'boolean',
            ],
        ]);

        // El seguimiento debe pertenecer a la misma orden.
        $seguimiento = null;

        if (! empty($datos['seguimiento_id'])) {
            $seguimiento = $orden->seguimientos()
                ->findOrFail($datos['seguimiento_id']);
        }

        $visible = $request->boolean('visible_cliente');

        // No publicar evidencias asociadas a notas internas.
        if ($visible && $seguimiento && ! $seguimiento->visible_cliente) {
            return response()->json([
                'message' => 'El seguimiento asociado es interno',
            ], 422);
        }

        $archivo = $request->file('archivo');

        $nombre = Str::uuid()->toString()
            . '.'
            . $archivo->extension();

        $directorio = 'establecimientos/'
            . $orden->establecimiento_id
            . '/ordenes/'
            . $orden->id;

        $ruta = $directorio . '/' . $nombre;

        $disk = Storage::disk('evidencias');

        // Guardar el archivo en Supabase Storage.
        try {
            $disk->putFileAs(
                $directorio,
                $archivo,
                $nombre,
                [
                    'ContentType' => $archivo->getMimeType(),
                ]
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo almacenar el archivo',
            ], 503);
        }

        // Registrar los metadatos en PostgreSQL.
        try {
            $evidencia = Evidencia::create([
                'orden_id' => $orden->id,
                'seguimiento_id' => $seguimiento?->id,
                'usuario_id' => $request->user()->id,
                'ruta_archivo' => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'tipo_mime' => $archivo->getMimeType(),
                'tamano_bytes' => $archivo->getSize(),
                'descripcion' => $datos['descripcion'] ?? null,
                'visible_cliente' => $visible,
            ]);
        } catch (Throwable $e) {
            // Intentar eliminar el archivo si falla la BD.
            try {
                $disk->delete($ruta);
            } catch (Throwable $errorLimpieza) {
                report($errorLimpieza);
            }

            report($e);

            return response()->json([
                'message' => 'No se pudo registrar la evidencia',
            ], 500);
        }

        return response()->json(
            $evidencia->fresh(),
            201
        );
    }

    // Descargar una fotografía de forma protegida.
    public function archivo(Request $request)
    {
        $orden = $this->ordenVisible($request);

        $evidencia = $this->evidenciaVisible(
            $request,
            $orden
        );

        $disk = Storage::disk('evidencias');

        if (! $disk->exists($evidencia->ruta_archivo)) {
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
    }

    // Actualizar descripción y visibilidad.
    public function update(Request $request)
    {
        $orden = $this->ordenVisible($request);

        $evidencia = $this->evidenciaVisible(
            $request,
            $orden
        );

        if (in_array($orden->estado, self::CERRADAS, true)) {
            return response()->json([
                'message' => 'La orden está cerrada',
            ], 422);
        }

        $datos = $request->validate([
            'descripcion' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],
            'visible_cliente' => [
                'sometimes',
                'boolean',
            ],
        ]);

        if (
            ($datos['visible_cliente'] ?? false)
            && $evidencia->seguimiento_id
            && ! $orden->seguimientos()
                ->whereKey($evidencia->seguimiento_id)
                ->where('visible_cliente', true)
                ->exists()
        ) {
            return response()->json([
                'message' => 'El seguimiento asociado es interno',
            ], 422);
        }

        $evidencia->update($datos);

        return response()->json(
            $evidencia->fresh()
        );
    }

    // Eliminar una evidencia.
    public function destroy(Request $request)
    {
        $orden = $this->ordenVisible($request);

        $evidencia = $this->evidenciaVisible(
            $request,
            $orden
        );

        if (in_array($orden->estado, self::CERRADAS, true)) {
            return response()->json([
                'message' => 'La orden está cerrada',
            ], 422);
        }

        // Solo el administrador puede eliminar evidencias.
        if ($this->membresia($request)->rol !== 'administrador') {
            return response()->json([
                'message' => 'No tienes permiso para eliminar evidencias',
            ], 403);
        }

        $ruta = $evidencia->ruta_archivo;

        $evidencia->delete();

        try {
            Storage::disk('evidencias')->delete($ruta);
        } catch (Throwable $e) {
            // Registrar el fallo para una limpieza posterior.
            report($e);

            return response()->json([
                'message' => 'Registro eliminado; limpieza del archivo pendiente',
            ], 202);
        }

        return response()->noContent();
    }
}