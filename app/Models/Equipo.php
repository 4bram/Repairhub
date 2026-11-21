<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipo extends Model
{
    use SoftDeletes;

    protected $table = 'equipos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'establecimiento_id', 'cliente_id', 'categoria_id', 'nombre', 'marca',
        'modelo', 'numero_serie', 'caracteristicas', 'observaciones',
    ];

    protected function casts(): array
    {
        return ['caracteristicas' => 'object'];
    }
}