<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $datos = $request->validate([
            'correo' => ['required', 'email'],
            'contrasena' => ['required', 'string'],
        ]);

        $usuario = Usuario::whereRaw('lower(correo) = ?', [strtolower($datos['correo'])])->first();

        if (! $usuario || ! $usuario->activo || ! Hash::check($datos['contrasena'], $usuario->contrasena)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        return response()->json([
            'token' => $usuario->createToken('api')->plainTextToken,
            'usuario' => $usuario,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}