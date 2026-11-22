<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes_servicio';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'establecimiento_id', 'cliente_id', 'equipo_id', 'cita_id', 'tecnico_membresia_id',
        'folio', 'problema_reportado', 'estado', 'prioridad', 'estado_fisico_inicial',
        'accesorios_recibidos', 'fecha_estimada', 'fecha_entrega',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'datetime',
            'fecha_estimada' => 'datetime',
            'fecha_entrega' => 'datetime',
        ];
    }

    public function seguimientos()
    {
        return $this->hasMany(Seguimiento::class, 'orden_id');
    }

    public function diagnosticos()
    {
        return $this->hasMany(Diagnostico::class, 'orden_id');
    }
}