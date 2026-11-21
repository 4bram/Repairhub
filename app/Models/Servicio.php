<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicios';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'establecimiento_id', 'nombre', 'descripcion', 'precio_base',
        'duracion_estimada_minutos', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}