<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\UsuarioEstablecimiento;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    private function eid(Request $request): int
    {
        return $request->attributes->get('membresia')->establecimiento_id;
    }

    public function index(Request $request)
    {
        $filas = UsuarioEstablecimiento::where('usuarios_establecimientos.establecimiento_id', $this->eid($request))
            ->where('usuarios_establecimientos.rol', 'tecnico')
            ->join('usuarios', 'usuarios.id', '=', 'usuarios_establecimientos.usuario_id')
            ->whereNull('usuarios.eliminado_en')
            ->orderBy('usuarios.nombre')
            ->get([
                'usuarios_establecimientos.id as membresia_id',
                'usuarios.id as usuario_id',
                'usuarios.nombre',
                'usuarios.correo',
                'usuarios_establecimientos.activo',
            ]);

        return response()->json($filas);
    }

    public function store(Request $request)
    {
        $eid = $this->eid($request);

        $datos = $request->validate([
            'correo' => ['required', 'email'],
        ]);

        $usuario = Usuario::whereRaw('lower(correo) = ?', [strtolower($datos['correo'])])->first();

        if (! $usuario || ! $usuario->activo) {
            return response()->json([
                'message' => 'Ese correo no está registrado. Pide a la persona que cree su cuenta primero.',
            ], 404);
        }

        $m = UsuarioEstablecimiento::where('establecimiento_id', $eid)
            ->where('usuario_id', $usuario->id)
            ->first();

        if ($m) {
            if ($m->rol === 'administrador') {
                return response()->json(['message' => 'Esa persona ya es administradora de este negocio'], 409);
            }
            if ($m->activo) {
                return response()->json(['message' => 'Esa persona ya es técnico de este negocio'], 409);
            }
            $m->activo = true;
            $m->save();
        } else {
            $m = UsuarioEstablecimiento::create([
                'establecimiento_id' => $eid,
                'usuario_id' => $usuario->id,
                'rol' => 'tecnico',
            ]);
        }

        return response()->json([
            'membresia_id' => $m->id,
            'usuario_id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'correo' => $usuario->correo,
            'activo' => true,
        ], 201);
    }

    public function destroy(Request $request)
    {
        $m = UsuarioEstablecimiento::where('establecimiento_id', $this->eid($request))
            ->where('rol', 'tecnico')
            ->findOrFail($request->route('membresia_id'));

        $m->activo = false;
        $m->save();

        return response()->json(['message' => 'Técnico desactivado']);
    }
}