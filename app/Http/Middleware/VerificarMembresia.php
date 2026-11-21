<?php

namespace App\Http\Middleware;

use App\Models\UsuarioEstablecimiento;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarMembresia
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $membresia = UsuarioEstablecimiento::where('usuario_id', $request->user()->id)
            ->where('establecimiento_id', $request->route('establecimiento_id'))
            ->where('activo', true)
            ->whereHas('establecimiento', fn ($q) => $q->where('activo', true))
            ->first();

        if (! $membresia) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        if ($roles && ! in_array($membresia->rol, $roles, true)) {
            return response()->json(['message' => 'No tienes permiso para esta acción'], 403);
        }

        $request->attributes->set('membresia', $membresia);

        return $next($request);
    }
}