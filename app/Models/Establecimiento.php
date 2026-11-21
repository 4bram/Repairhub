<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Establecimiento extends Model
{
    use SoftDeletes;

    protected $table = 'establecimientos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = ['nombre', 'slug', 'descripcion', 'correo', 'telefono', 'direccion'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'horario_atencion' => 'object',
        ];
    }
}