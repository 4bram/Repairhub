<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioEstablecimiento extends Model
{
    protected $table = 'usuarios_establecimientos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = ['establecimiento_id', 'usuario_id', 'rol'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }
}