<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    private function eid(Request $request): int
    {
        return $request->attributes->get('membresia')->establecimiento_id;
    }

    private function correoEnUso(int $eid, string $correo, ?int $exceptoId = null): bool
    {
        return Cliente::where('establecimiento_id', $eid)
            ->whereRaw('lower(correo) = ?', [strtolower($correo)])
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->exists();
    }

    public function index(Request $request)
    {
        $q = Cliente::where('establecimiento_id', $this->eid($request));

        if ($b = $request->query('buscar')) {
            $q->where(function ($w) use ($b) {
                $w->where('nombre', 'ilike', "%{$b}%")
                    ->orWhere('correo', 'ilike', "%{$b}%")
                    ->orWhere('telefono', 'like', "%{$b}%");
            });
        }

        return response()->json($q->orderBy('nombre')->limit(100)->get());
    }

    public function show(Request $request)
    {
        $cliente = Cliente::where('establecimiento_id', $this->eid($request))
            ->with('equipos')
            ->findOrFail($request->route('cliente_id'));

        return response()->json($cliente);
    }

    public function store(Request $request)
    {
        $eid = $this->eid($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'correo' => ['nullable', 'email', 'max:180', 'required_without:telefono'],
            'telefono' => ['nullable', 'string', 'max:25', 'required_without:correo'],
            'observaciones' => ['nullable', 'string'],
        ]);

        if (! empty($datos['correo']) && $this->correoEnUso($eid, $datos['correo'])) {
            return response()->json(['message' => 'Ya existe un cliente con ese correo'], 409);
        }

        $cliente = Cliente::create([...$datos, 'establecimiento_id' => $eid]);

        return response()->json($cliente->fresh(), 201);
    }

    public function update(Request $request)
    {
        $eid = $this->eid($request);

        $cliente = Cliente::where('establecimiento_id', $eid)
            ->findOrFail($request->route('cliente_id'));

        $datos = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:120'],
            'correo' => ['sometimes', 'nullable', 'email', 'max:180'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:25'],
            'observaciones' => ['sometimes', 'nullable', 'string'],
        ]);

        if (! empty($datos['correo']) && $this->correoEnUso($eid, $datos['correo'], $cliente->id)) {
            return response()->json(['message' => 'Ya existe un cliente con ese correo'], 409);
        }

        $cliente->fill($datos);

        if (blank($cliente->correo) && blank($cliente->telefono)) {
            return response()->json(['message' => 'El cliente debe tener al menos un correo o un teléfono'], 422);
        }

        $cliente->save();

        return response()->json($cliente->fresh());
    }

    public function destroy(Request $request)
    {
        $cliente = Cliente::where('establecimiento_id', $this->eid($request))
            ->findOrFail($request->route('cliente_id'));

        $cliente->delete();

        return response()->json(['message' => 'Cliente eliminado']);
    }
}