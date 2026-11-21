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
            'tipo_token' => 'Bearer',
            'usuario' => $usuario,
        ]);
    }
    public function registro(Request $request){
        $datos = $request->validate([
            'nombre' => ['required','string','max:120'],
            'correo' => ['required','string','max:200'], 
            'contrasena' => ['required','string','min:8', 'max:120'],
            'telefono' => ['nullable','string','max:25'],
        ]); 

        $correo = strtolower(trim($datos['correo']));

        if (Usuario::whereRaw('lower(correo) = ?', [$correo])->exists()) {
            return response()->json(['message' => 'Ya existe una cuenta con ese correo'], 409);
        }

        $usuario = Usuario::create([
            'nombre' => $datos['nombre'],
            'correo' => $correo,
            'contrasena' => $datos['contrasena'],
            'telefono' => $datos['telefono'] ?? null,
        ]);
        $usuario->refresh();

        return response()->json([
            'token' => $usuario->createToken('api')->plainTextToken,
            'tipo_token' => 'Bearer',
            'usuario' => $usuario,
        ], 201);
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