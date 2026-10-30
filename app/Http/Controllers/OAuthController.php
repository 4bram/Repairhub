<?php

namespace App\Http\Controllers;

use App\Services\GoogleOAuthService;
use App\Services\CodigoOAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

class OAuthController extends Controller
{
    public function redirigirGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callbackGoogle(
        GoogleOAuthService $googleOAuthService,
        CodigoOAuthService $codigoOAuthService
    ): RedirectResponse {
        try {
            $googleUser = Socialite::driver('google')->user();

            $usuario = $googleOAuthService->autenticar(
                $googleUser
            );

            $codigo = $codigoOAuthService->generar($usuario);

            $frontend = rtrim(
                config('app.frontend_url'),
                '/'
            );

            return redirect()->away(
                $frontend . '/auth/callback?code='
                . urlencode($codigo)
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
