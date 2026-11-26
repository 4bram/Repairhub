<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\UsuarioEstablecimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EstablecimientoController extends Controller
{
    public function index(Request $request)
    {
        $membresias = UsuarioEstablecimiento::with('establecimiento')
            ->where('usuario_id', $request->user()->id)
            ->where('activo', true)
            ->whereHas('establecimiento')
            ->get();

        return response()->json($membresias->map(fn ($m) => [
            'id' => $m->establecimiento->id,
            'nombre' => $m->establecimiento->nombre,
            'slug' => $m->establecimiento->slug,
            'rol' => $m->rol,
        ]));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'correo' => ['nullable', 'email', 'max:180'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'direccion' => ['nullable', 'string'],
        ]);

        $base = rtrim(substr(Str::slug($datos['nombre']), 0, 100), '-');
        if ($base === '') {
            return response()->json(['message' => 'El nombre debe contener letras o números'], 422);
        }

        $slug = $base;
        $n = 2;
        while (Establecimiento::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        $establecimiento = DB::transaction(function () use ($datos, $slug, $request) {
            $e = Establecimiento::create([...$datos, 'slug' => $slug]);

            UsuarioEstablecimiento::create([
                'establecimiento_id' => $e->id,
                'usuario_id' => $request->user()->id,
                'rol' => 'administrador',
            ]);

            return $e;
        });

        return response()->json($establecimiento->fresh(), 201);
    }
        public function show(Request $request)
    {
        $m = $request->attributes->get('membresia');

        return response()->json([...$m->establecimiento->toArray(), 'rol' => $m->rol]);
    }

    public function update(Request $request)
    {
        $m = $request->attributes->get('membresia');

        $datos = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'correo' => ['nullable', 'email', 'max:180'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'direccion' => ['nullable', 'string'],
            'horario_atencion' => ['sometimes', 'nullable', 'array'],
        ]);

        if (array_key_exists('horario_atencion', $datos)) {
            $datos['horario_atencion'] = (object) ($datos['horario_atencion'] ?? []);
        }

        $m->establecimiento->update($datos);

        return response()->json($m->establecimiento->fresh());
    }
}