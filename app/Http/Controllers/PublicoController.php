<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Establecimiento;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicoController extends Controller
{
    private function negocio(Request $request): Establecimiento
    {
        return Establecimiento::where('slug', $request->route('slug'))
            ->where('activo', true)
            ->firstOrFail();
    }

    public function mostrar(Request $request)
    {
        $e = $this->negocio($request);

        $servicios = Servicio::where('establecimiento_id', $e->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion', 'precio_base', 'duracion_estimada_minutos']);

        return response()->json([
            'nombre' => $e->nombre,
            'slug' => $e->slug,
            'descripcion' => $e->descripcion,
            'telefono' => $e->telefono,
            'correo' => $e->correo,
            'direccion' => $e->direccion,
            'horario_atencion' => $e->horario_atencion,
            'servicios' => $servicios,
        ]);
    }

    public function solicitarCita(Request $request)
    {
        $e = $this->negocio($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'correo' => ['nullable', 'email', 'max:180', 'required_without:telefono'],
            'telefono' => ['nullable', 'string', 'max:25', 'required_without:correo'],
            'servicio_id' => ['nullable', 'integer'],
            'fecha_programada' => ['required', 'date', 'after:now', 'before:+1 year'],
            'descripcion_problema' => ['required', 'string', 'max:2000'],
        ]);

        if (! empty($datos['servicio_id']) &&
            ! Servicio::where('establecimiento_id', $e->id)->where('activo', true)->whereKey($datos['servicio_id'])->exists()) {
            return response()->json(['message' => 'El servicio seleccionado no está disponible'], 422);
        }

        $correo = ! empty($datos['correo']) ? strtolower($datos['correo']) : null;

        $cita = DB::transaction(function () use ($e, $datos, $correo) {
            $cliente = null;

            if ($correo) {
                $cliente = Cliente::where('establecimiento_id', $e->id)
                    ->whereRaw('lower(correo) = ?', [$correo])->first();
            }
            if (! $cliente && ! empty($datos['telefono'])) {
                $cliente = Cliente::where('establecimiento_id', $e->id)
                    ->where('telefono', $datos['telefono'])->first();
            }
            if (! $cliente) {
                $cliente = Cliente::create([
                    'establecimiento_id' => $e->id,
                    'nombre' => $datos['nombre'],
                    'correo' => $correo,
                    'telefono' => $datos['telefono'] ?? null,
                ]);
            }

            return Cita::create([
                'establecimiento_id' => $e->id,
                'cliente_id' => $cliente->id,
                'servicio_id' => $datos['servicio_id'] ?? null,
                'fecha_programada' => $datos['fecha_programada'],
                'descripcion_problema' => $datos['descripcion_problema'],
            ]);
        });

        return response()->json([
            'message' => 'Solicitud recibida. El negocio la confirmará pronto.',
            'cita' => [
                'id' => $cita->id,
                'fecha_programada' => $cita->fresh()->fecha_programada,
                'estado' => 'pendiente',
            ],
        ], 201);
    }

    public function establecimientos()
    {
        $establecimientos = \App\Models\Establecimiento::where('activo', true)
            ->get()
            ->map(function ($est) {
                $servicios = \App\Models\Servicio::where('establecimiento_id', $est->id)
                    ->where('activo', true)
                    ->pluck('nombre');

                return [
                    'id' => $est->id,
                    'nombre' => $est->nombre,
                    'slug' => $est->slug,
                    'descripcion' => $est->descripcion,
                    'telefono' => $est->telefono,
                    'correo' => $est->correo,
                    'direccion' => $est->direccion,
                    'activo' => $est->activo,
                    'servicios' => $servicios,
                ];
            });

        return response()->json($establecimientos);
    }
}