<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $table = 'citas';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'establecimiento_id', 'cliente_id', 'servicio_id', 'fecha_programada',
        'descripcion_problema', 'estado', 'notas_internas',
    ];

    protected function casts(): array
    {
        return ['fecha_programada' => 'datetime'];
    }
}