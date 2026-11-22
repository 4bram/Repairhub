<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    protected $table = 'diagnosticos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = ['orden_id', 'tecnico_membresia_id', 'descripcion', 'reparacion_sugerida'];
}