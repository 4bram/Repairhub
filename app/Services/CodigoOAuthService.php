<?php

namespace App\Services;

use App\Models\CodigoOAuth;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CodigoOAuthService
{
    public function generar(Usuario $usuario): string
    {
        $codigo = Str::random(64);

        CodigoOAuth::create([
            'usuario_id' => $usuario->id,
            'codigo_hash' => hash('sha256', $codigo),
            'expira_en' => now()->addSeconds(60),
        ]);

        return $codigo;
    }

    public function intercambiar(string $codigo): ?string
    {
        return DB::transaction(function () use ($codigo) {
            $registro = CodigoOAuth::query()
                ->where('codigo_hash', hash('sha256', $codigo))
                ->lockForUpdate()
                ->first();

            if (
                !$registro ||
                $registro->utilizado_en !== null ||
                $registro->expira_en->isPast()
            ) {
                return null;
            }

            $usuario = $registro->usuario;

            if (!$usuario || !$usuario->activo) {
                return null;
            }

            $registro->utilizado_en = now();
            $registro->save();

            return $usuario->createToken('google-oauth')->plainTextToken;
        });
    }
}
