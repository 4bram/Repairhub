<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Diagnostico;
use App\Models\Equipo;
use App\Models\Orden;
use App\Models\Seguimiento;
use App\Models\UsuarioEstablecimiento;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrdenController extends Controller
{
    private const TRANSICIONES = [
        'recibido' => ['en_diagnostico', 'cancelado'],
        'en_diagnostico' => ['esperando_autorizacion', 'no_reparable', 'cancelado'],
        'esperando_autorizacion' => ['en_reparacion', 'en_diagnostico', 'no_reparable', 'cancelado'],
        'en_reparacion' => ['en_pruebas', 'no_reparable', 'cancelado'],
        'en_pruebas' => ['listo_entrega', 'en_reparacion'],
        'listo_entrega' => ['entregado'],
        'no_reparable' => ['entregado'],
        'cancelado' => ['entregado'],
        'entregado' => [],
    ];

    // Solo el administrador entrega o cancela
    private const SOLO_ADMIN = ['entregado', 'cancelado'];

    private const CERRADAS = ['entregado', 'cancelado'];

    private function membresia(Request $request)
    {
        return $request->attributes->get('membresia');
    }

    // Administrador: todas las del negocio. Técnico: solo las asignadas a él.
    private function ordenesVisibles(Request $request)
    {
        $m = $this->membresia($request);

        $q = Orden::where('ordenes_servicio.establecimiento_id', $m->establecimiento_id);

        if ($m->rol === 'tecnico') {
            $q->where('ordenes_servicio.tecnico_membresia_id', $m->id);
        }

        return $q;
    }

    private function tecnicoValido(int $eid, $membresiaId): bool
    {
        return UsuarioEstablecimiento::where('establecimiento_id', $eid)
            ->where('rol', 'tecnico')
            ->where('activo', true)
            ->whereKey($membresiaId)
            ->exists();
    }

    private function generarFolio(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $sufijo = '';

        for ($i = 0; $i < 6; $i++) {
            $sufijo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return 'RH-'.now()->format('ymd').'-'.$sufijo;
    }

    public function index(Request $request)
    {
        $q = $this->ordenesVisibles($request)
            ->join('clientes', 'clientes.id', '=', 'ordenes_servicio.cliente_id')
            ->join('equipos', 'equipos.id', '=', 'ordenes_servicio.equipo_id')
            ->leftJoin('usuarios_establecimientos as ue', 'ue.id', '=', 'ordenes_servicio.tecnico_membresia_id')
            ->leftJoin('usuarios as ut', 'ut.id', '=', 'ue.usuario_id')
            ->select(
                'ordenes_servicio.*',
                'clientes.nombre as cliente_nombre',
                'equipos.marca as equipo_marca',
                'equipos.modelo as equipo_modelo',
                'ut.nombre as tecnico_nombre'
            );

        if ($estado = $request->query('estado')) {
            $q->where('ordenes_servicio.estado', $estado);
        }
        if ($prioridad = $request->query('prioridad')) {
            $q->where('ordenes_servicio.prioridad', $prioridad);
        }
        if ($request->boolean('activas')) {
            $q->whereNotIn('ordenes_servicio.estado', self::CERRADAS);
        }

        return response()->json($q->orderByDesc('ordenes_servicio.id')->limit(200)->get());
    }

    public function show(Request $request)
    {
        $orden = $this->ordenesVisibles($request)->findOrFail($request->route('orden_id'));

        $tecnico = null;
        if ($orden->tecnico_membresia_id) {
            $tecnico = DB::table('usuarios_establecimientos as ue')
                ->join('usuarios as u', 'u.id', '=', 'ue.usuario_id')
                ->where('ue.id', $orden->tecnico_membresia_id)
                ->first(['ue.id as membresia_id', 'u.nombre', 'u.correo']);
        }

        return response()->json([
            ...$orden->toArray(),
            'cliente' => Cliente::withTrashed()->find($orden->cliente_id),
            'equipo' => Equipo::withTrashed()->find($orden->equipo_id),
            'tecnico' => $tecnico,
            'seguimientos' => $orden->seguimientos()->orderBy('creado_en')->get(),
            'diagnosticos' => $orden->diagnosticos()->orderBy('creado_en')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $eid = $this->membresia($request)->establecimiento_id;

        $datos = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'equipo_id' => ['required', 'integer'],
            'cita_id' => ['nullable', 'integer'],
            'tecnico_membresia_id' => ['nullable', 'integer'],
            'problema_reportado' => ['required_without:cita_id', 'nullable', 'string', 'max:2000'],
            'prioridad' => ['sometimes', Rule::in(['baja', 'normal', 'alta'])],
            'estado_fisico_inicial' => ['nullable', 'string'],
            'accesorios_recibidos' => ['nullable', 'string'],
            'fecha_estimada' => ['nullable', 'date', 'after:now'],
        ]);

        $cliente = Cliente::where('establecimiento_id', $eid)->find($datos['cliente_id']);
        if (! $cliente) {
            return response()->json(['message' => 'El cliente no existe en este negocio'], 422);
        }

        $equipo = Equipo::where('establecimiento_id', $eid)
            ->where('cliente_id', $cliente->id)
            ->find($datos['equipo_id']);
        if (! $equipo) {
            return response()->json(['message' => 'El equipo no existe o no pertenece a ese cliente'], 422);
        }

        $cita = null;
        if (! empty($datos['cita_id'])) {
            $cita = Cita::where('establecimiento_id', $eid)
                ->where('cliente_id', $cliente->id)
                ->find($datos['cita_id']);

            if (! $cita) {
                return response()->json(['message' => 'La cita no existe o no es de ese cliente'], 422);
            }
            if ($cita->estado !== 'confirmada') {
                return response()->json(['message' => 'La cita debe estar confirmada para recibir el equipo'], 422);
            }
            if (Orden::where('cita_id', $cita->id)->exists()) {
                return response()->json(['message' => 'Esa cita ya tiene una orden'], 409);
            }
        }

        if (! empty($datos['tecnico_membresia_id']) && ! $this->tecnicoValido($eid, $datos['tecnico_membresia_id'])) {
            return response()->json(['message' => 'El técnico no existe o está inactivo en este negocio'], 422);
        }

        $orden = null;

        // El folio es aleatorio y único en toda la BD: si choca, se reintenta con otro.
        for ($i = 0; $i < 5 && ! $orden; $i++) {
            try {
                $orden = DB::transaction(function () use ($request, $eid, $datos, $cliente, $equipo, $cita) {
                    $o = Orden::create([
                        'establecimiento_id' => $eid,
                        'cliente_id' => $cliente->id,
                        'equipo_id' => $equipo->id,
                        'cita_id' => $cita?->id,
                        'tecnico_membresia_id' => $datos['tecnico_membresia_id'] ?? null,
                        'folio' => $this->generarFolio(),
                        'problema_reportado' => $datos['problema_reportado'] ?? $cita->descripcion_problema,
                        'prioridad' => $datos['prioridad'] ?? 'normal',
                        'estado_fisico_inicial' => $datos['estado_fisico_inicial'] ?? null,
                        'accesorios_recibidos' => $datos['accesorios_recibidos'] ?? null,
                        'fecha_estimada' => $datos['fecha_estimada'] ?? null,
                    ]);

                    Seguimiento::create([
                        'orden_id' => $o->id,
                        'usuario_id' => $request->user()->id,
                        'estado' => 'recibido',
                        'descripcion' => 'Equipo recibido en el taller',
                        'visible_cliente' => true,
                    ]);

                    if ($cita) {
                        $cita->update(['estado' => 'completada']);
                    }

                    return $o;
                });
            } catch (UniqueConstraintViolationException $e) {
                // folio repetido: siguiente intento
            }
        }

        if (! $orden) {
            return response()->json(['message' => 'No se pudo crear la orden, intenta de nuevo'], 409);
        }

        return response()->json($orden->fresh(), 201);
    }

    public function update(Request $request)
    {
        $eid = $this->membresia($request)->establecimiento_id;

        $orden = Orden::where('establecimiento_id', $eid)->findOrFail($request->route('orden_id'));

        if (in_array($orden->estado, self::CERRADAS, true)) {
            return response()->json(['message' => 'La orden está cerrada y ya no se puede editar'], 422);
        }

        $datos = $request->validate([
            'problema_reportado' => ['sometimes', 'required', 'string', 'max:2000'],
            'prioridad' => ['sometimes', Rule::in(['baja', 'normal', 'alta'])],
            'estado_fisico_inicial' => ['sometimes', 'nullable', 'string'],
            'accesorios_recibidos' => ['sometimes', 'nullable', 'string'],
            'fecha_estimada' => ['sometimes', 'nullable', 'date', 'after:now'],
            'tecnico_membresia_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $cambioTecnico = array_key_exists('tecnico_membresia_id', $datos)
            && $datos['tecnico_membresia_id'] != $orden->tecnico_membresia_id;

        if ($cambioTecnico && ! empty($datos['tecnico_membresia_id']) && ! $this->tecnicoValido($eid, $datos['tecnico_membresia_id'])) {
            return response()->json(['message' => 'El técnico no existe o está inactivo en este negocio'], 422);
        }

        DB::transaction(function () use ($request, $orden, $datos, $cambioTecnico) {
            $orden->update($datos);

            if ($cambioTecnico) {
                Seguimiento::create([
                    'orden_id' => $orden->id,
                    'usuario_id' => $request->user()->id,
                    'estado' => $orden->estado,
                    'descripcion' => 'Técnico reasignado',
                    'visible_cliente' => false,
                ]);
            }
        });

        return response()->json($orden->fresh());
    }

    public function cambiarEstado(Request $request)
    {
        $m = $this->membresia($request);

        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(self::TRANSICIONES))],
            'descripcion' => ['required', 'string', 'max:2000'],
            'visible_cliente' => ['sometimes', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $m, $datos) {
            $orden = $this->ordenesVisibles($request)
                ->lockForUpdate()
                ->findOrFail($request->route('orden_id'));

            if (! in_array($datos['estado'], self::TRANSICIONES[$orden->estado], true)) {
                return response()->json([
                    'message' => "No se puede pasar de {$orden->estado} a {$datos['estado']}",
                ], 422);
            }

            if ($m->rol === 'tecnico' && in_array($datos['estado'], self::SOLO_ADMIN, true)) {
                return response()->json([
                    'message' => 'Solo un administrador puede marcar la orden como entregada o cancelada',
                ], 403);
            }

            if ($datos['estado'] === 'en_reparacion' &&
                ! DB::table('cotizaciones')->where('orden_id', $orden->id)->where('estado', 'aprobada')->exists()) {
                return response()->json([
                    'message' => 'La orden necesita una cotización aprobada por el cliente para pasar a reparación',
                ], 422);
            }

            $orden->estado = $datos['estado'];
            if ($datos['estado'] === 'entregado') {
                $orden->fecha_entrega = now();
            }
            $orden->save();

            Seguimiento::create([
                'orden_id' => $orden->id,
                'usuario_id' => $request->user()->id,
                'estado' => $datos['estado'],
                'descripcion' => $datos['descripcion'],
                'visible_cliente' => $datos['visible_cliente'] ?? true,
            ]);

            return response()->json($orden->fresh());
        });
    }

    // Nota de avance. Por defecto es interna; el cliente solo la ve si visible_cliente = true.
    public function nota(Request $request)
    {
        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'max:2000'],
            'visible_cliente' => ['sometimes', 'boolean'],
        ]);

        $orden = $this->ordenesVisibles($request)->findOrFail($request->route('orden_id'));

        if (in_array($orden->estado, self::CERRADAS, true)) {
            return response()->json(['message' => 'La orden está cerrada'], 422);
        }

        $seguimiento = Seguimiento::create([
            'orden_id' => $orden->id,
            'usuario_id' => $request->user()->id,
            'estado' => $orden->estado,
            'descripcion' => $datos['descripcion'],
            'visible_cliente' => $datos['visible_cliente'] ?? false,
        ]);

        return response()->json($seguimiento->fresh(), 201);
    }

    public function diagnostico(Request $request)
    {
        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'max:5000'],
            'reparacion_sugerida' => ['nullable', 'string', 'max:5000'],
        ]);

        $orden = $this->ordenesVisibles($request)->findOrFail($request->route('orden_id'));

        if (in_array($orden->estado, self::CERRADAS, true)) {
            return response()->json(['message' => 'La orden está cerrada'], 422);
        }

        $diagnostico = Diagnostico::create([
            'orden_id' => $orden->id,
            'tecnico_membresia_id' => $this->membresia($request)->id,
            'descripcion' => $datos['descripcion'],
            'reparacion_sugerida' => $datos['reparacion_sugerida'] ?? null,
        ]);

        return response()->json($diagnostico->fresh(), 201);
    }
}