<?php

namespace App\Http\Controllers;

use App\Services\GoogleOAuthService;
use App\Services\CodigoOAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;
use Illuminate\Support\Facades\Log;
class OAuthController extends Controller
{
    public function redirigirGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callbackGoogle(
        Request $request,
        GoogleOAuthService $googleOAuthService
    ): RedirectResponse {
        try {
            // Obtener la identidad de Google.
            $googleUser = Socialite::driver('google')->user();

            // Mantener la lógica original de usuarios y cuentas OAuth.
            $usuario = $googleOAuthService->autenticar($googleUser);

            // Iniciar sesión con el mismo guard utilizado
            // por el AuthController de nuestro compañero.
            Auth::guard('web')->login($usuario);

            // Regenerar la sesión para evitar fijación de sesión.
            $request->session()->regenerate();

            // Redirigir al frontend sin enviar tokens ni códigos.
            $frontend = rtrim(config('app.frontend_url'), '/');

            return redirect()->away(
                $frontend . '/auth/callback'
            );

        } catch (Throwable $e) {
            report($e);

            return redirect()->away(
                rtrim(config('app.frontend_url'), '/')
                . '/login?error=oauth'
            );
        }
    }

    public function intercambiarCodigo(
        Request $request,
        CodigoOAuthService $codigoOAuthService
    ): JsonResponse {
        $datos = $request->validate([
            'code' => ['required', 'string', 'size:64'],
        ]);

        $token = $codigoOAuthService->intercambiar(
            $datos['code']
        );

        if (!$token) {
            return response()->json([
                'mensaje' => 'Código inválido, utilizado o expirado.'
            ], 401);
        }

        return response()->json([
            'token' => $token,
            'tipo_token' => 'Bearer',
        ]);
    }
}
