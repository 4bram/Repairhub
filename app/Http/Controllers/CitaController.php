<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CitaController extends Controller
{
    private const TRANSICIONES = [
        'pendiente' => ['confirmada', 'cancelada'],
        'confirmada' => ['completada', 'cancelada', 'no_asistio'],
        'cancelada' => [],
        'completada' => [],
        'no_asistio' => [],
    ];

    private function eid(Request $request): int
    {
        return $request->attributes->get('membresia')->establecimiento_id;
    }

    private function conDetalle(int $eid)
    {
        return Cita::where('citas.establecimiento_id', $eid)
            ->join('clientes', 'clientes.id', '=', 'citas.cliente_id')
            ->leftJoin('servicios', 'servicios.id', '=', 'citas.servicio_id')
            ->select(
                'citas.*',
                'clientes.nombre as cliente_nombre',
                'clientes.telefono as cliente_telefono',
                'clientes.correo as cliente_correo',
                'servicios.nombre as servicio_nombre'
            );
    }

    public function index(Request $request)
    {
        $q = $this->conDetalle($this->eid($request));

        if ($estado = $request->query('estado')) {
            $q->where('citas.estado', $estado);
        }
        if ($desde = $request->query('desde')) {
            $q->where('citas.fecha_programada', '>=', $desde);
        }
        if ($hasta = $request->query('hasta')) {
            $q->where('citas.fecha_programada', '<=', $hasta);
        }

        return response()->json($q->orderBy('citas.fecha_programada')->limit(200)->get());
    }

    public function show(Request $request)
    {
        $cita = $this->conDetalle($this->eid($request))
            ->where('citas.id', $request->route('cita_id'))
            ->firstOrFail();

        return response()->json($cita);
    }

    public function store(Request $request)
    {
        $eid = $this->eid($request);

        $datos = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'servicio_id' => ['nullable', 'integer'],
            'fecha_programada' => ['required', 'date', 'after:now'],
            'descripcion_problema' => ['required', 'string', 'max:2000'],
            'notas_internas' => ['nullable', 'string'],
            'estado' => ['sometimes', Rule::in(['pendiente', 'confirmada'])],
        ]);

        if (! Cliente::where('establecimiento_id', $eid)->whereKey($datos['cliente_id'])->exists()) {
            return response()->json(['message' => 'El cliente no existe en este negocio'], 422);
        }

        if (! empty($datos['servicio_id']) &&
            ! Servicio::where('establecimiento_id', $eid)->where('activo', true)->whereKey($datos['servicio_id'])->exists()) {
            return response()->json(['message' => 'El servicio no existe o está desactivado'], 422);
        }

        $cita = Cita::create([...$datos, 'establecimiento_id' => $eid]);

        return response()->json($cita->fresh(), 201);
    }

    public function update(Request $request)
    {
        $eid = $this->eid($request);

        $cita = Cita::where('establecimiento_id', $eid)->findOrFail($request->route('cita_id'));

        $datos = $request->validate([
            'estado' => ['sometimes', Rule::in(array_keys(self::TRANSICIONES))],
            'fecha_programada' => ['sometimes', 'date', 'after:now'],
            'servicio_id' => ['sometimes', 'nullable', 'integer'],
            'descripcion_problema' => ['sometimes', 'required', 'string', 'max:2000'],
            'notas_internas' => ['sometimes', 'nullable', 'string'],
        ]);

        $cerrada = empty(self::TRANSICIONES[$cita->estado]);
        if ($cerrada && array_diff(array_keys($datos), ['notas_internas'])) {
            return response()->json(['message' => 'La cita ya está cerrada; solo se pueden editar las notas'], 422);
        }

        if (isset($datos['estado']) && $datos['estado'] !== $cita->estado &&
            ! in_array($datos['estado'], self::TRANSICIONES[$cita->estado], true)) {
            return response()->json([
                'message' => "No se puede pasar de {$cita->estado} a {$datos['estado']}",
            ], 422);
        }

        if (! empty($datos['servicio_id']) &&
            ! Servicio::where('establecimiento_id', $eid)->whereKey($datos['servicio_id'])->exists()) {
            return response()->json(['message' => 'El servicio no existe en este negocio'], 422);
        }

        $cita->update($datos);

        return response()->json($cita->fresh());
    }
}