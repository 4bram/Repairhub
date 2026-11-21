<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EquipoController extends Controller
{
    private function eid(Request $request): int
    {
        return $request->attributes->get('membresia')->establecimiento_id;
    }

    public function categorias()
    {
        $campos = config('equipos.campos', []);

        $categorias = DB::table('categorias_equipos')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion'])
            ->map(function ($c) use ($campos) {
                $c->campos = $campos[$c->nombre] ?? [];

                return $c;
            });

        return response()->json($categorias);
    }

    public function index(Request $request)
    {
        $equipos = Equipo::where('establecimiento_id', $this->eid($request))
            ->when($request->query('cliente_id'), fn ($q, $v) => $q->where('cliente_id', $v))
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json($equipos);
    }

    public function show(Request $request)
    {
        $equipo = Equipo::where('establecimiento_id', $this->eid($request))
            ->findOrFail($request->route('equipo_id'));

        return response()->json($equipo);
    }

    public function store(Request $request)
    {
        $eid = $this->eid($request);

        $datos = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'categoria_id' => ['required', 'integer', Rule::exists('categorias_equipos', 'id')->where('activo', true)],
            'nombre' => ['nullable', 'string', 'max:120'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:120'],
            'caracteristicas' => ['nullable', 'array'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $existeCliente = Cliente::where('establecimiento_id', $eid)
            ->whereKey($datos['cliente_id'])
            ->exists();

        if (! $existeCliente) {
            return response()->json(['message' => 'El cliente no existe en este negocio'], 422);
        }

        $equipo = Equipo::create([
            ...$datos,
            'establecimiento_id' => $eid,
            'caracteristicas' => (object) ($datos['caracteristicas'] ?? []),
        ]);

        return response()->json($equipo->fresh(), 201);
    }

    public function update(Request $request)
    {
        $equipo = Equipo::where('establecimiento_id', $this->eid($request))
            ->findOrFail($request->route('equipo_id'));

        $datos = $request->validate([
            'categoria_id' => ['sometimes', 'required', 'integer', Rule::exists('categorias_equipos', 'id')->where('activo', true)],
            'nombre' => ['sometimes', 'nullable', 'string', 'max:120'],
            'marca' => ['sometimes', 'nullable', 'string', 'max:100'],
            'modelo' => ['sometimes', 'nullable', 'string', 'max:100'],
            'numero_serie' => ['sometimes', 'nullable', 'string', 'max:120'],
            'caracteristicas' => ['sometimes', 'nullable', 'array'],
            'observaciones' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('caracteristicas', $datos)) {
            $datos['caracteristicas'] = (object) ($datos['caracteristicas'] ?? []);
        }

        $equipo->update($datos);

        return response()->json($equipo->fresh());
    }

    public function destroy(Request $request)
    {
        $equipo = Equipo::where('establecimiento_id', $this->eid($request))
            ->findOrFail($request->route('equipo_id'));

        $equipo->delete();

        return response()->json(['message' => 'Equipo eliminado']);
    }
}