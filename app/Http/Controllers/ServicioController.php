<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    private function eid(Request $request): int
    {
        return $request->attributes->get('membresia')->establecimiento_id;
    }

    public function index(Request $request)
    {
        return response()->json(
            Servicio::where('establecimiento_id', $this->eid($request))->orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'precio_base' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'duracion_estimada_minutos' => ['nullable', 'integer', 'min:1', 'max:10080'],
        ]);

        $servicio = Servicio::create([...$datos, 'establecimiento_id' => $this->eid($request)]);

        return response()->json($servicio->fresh(), 201);
    }

    public function update(Request $request)
    {
        $servicio = Servicio::where('establecimiento_id', $this->eid($request))
            ->findOrFail($request->route('servicio_id'));

        $datos = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'precio_base' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999'],
            'duracion_estimada_minutos' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10080'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $servicio->update($datos);

        return response()->json($servicio->fresh());
    }

    // No se borra: las citas y órdenes pueden referenciarlo. Se desactiva.
    public function destroy(Request $request)
    {
        $servicio = Servicio::where('establecimiento_id', $this->eid($request))
            ->findOrFail($request->route('servicio_id'));

        $servicio->activo = false;
        $servicio->save();

        return response()->json(['message' => 'Servicio desactivado']);
    }
}