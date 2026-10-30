<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\CuentaOAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as GoogleUser;
use RuntimeException;

class GoogleOAuthService
{
    public function autenticar(GoogleUser $googleUser): Usuario
    {
        $sub = $googleUser->getId();
        $correo = $googleUser->getEmail();
        $nombre = $googleUser->getName();

        if (!$sub || !$correo) {
            throw new RuntimeException(
                'Google no proporcionó una identidad válida.'
            );
        }

        $correo = Str::lower(trim($correo));

        // Socialite devuelve atributos del proveedor.
        $atributos = $googleUser->getRaw();

        if (($atributos['email_verified'] ?? false) !== true) {
            throw new RuntimeException(
                'Google no confirmó la verificación del correo.'
            );
        }

        return DB::transaction(function () use (
            $sub,
            $correo,
            $nombre
        ) {
            // 1. Buscar la identidad estable de Google.
            $cuenta = CuentaOAuth::query()
                ->where('proveedor', 'google')
                ->where('proveedor_usuario_id', $sub)
                ->first();

            if ($cuenta) {
                $usuario = Usuario::find($cuenta->usuario_id);

                if (!$usuario || !$usuario->activo) {
                    throw new RuntimeException(
                        'La cuenta no está disponible.'
                    );
                }

                return $usuario;
            }

            // 2. Evitar vincular por correo automáticamente.
            $usuarioExistente = Usuario::query()
                ->whereRaw('lower(correo) = ?', [$correo])
                ->first();

            if ($usuarioExistente) {
                throw new RuntimeException(
                    'Este correo ya está registrado. '
                    . 'Inicia sesión con tu método actual '
                    . 'para vincular Google.'
                );
            }

            // 3. Crear usuario nuevo.
            $usuario = Usuario::create([
                'nombre' => $nombre ?: 'Usuario Google',
                'correo' => $correo,
                'contrasena' => null,
                'correo_verificado_en' => now(),
            ]);

            // 4. Vincular la identidad Google mediante sub.
            CuentaOAuth::create([
                'usuario_id' => $usuario->id,
                'proveedor' => 'google',
                'proveedor_usuario_id' => $sub,
                'correo_proveedor' => $correo,
            ]);

            return $usuario;
        });
    }
}
