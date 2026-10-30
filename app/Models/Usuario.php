<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'usuarios';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = ['nombre', 'correo', 'contrasena', 'telefono'];

    protected $hidden = ['contrasena'];

    protected function casts(): array
    {
        return [
            'correo_verificado_en' => 'datetime',
            'activo' => 'boolean',
            'contrasena' => 'hashed',
        ];
    }

    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    public function cuentasOAuth()
    {
        return $this->hasMany(
            CuentaOAuth::class,
            'usuario_id'
        );
    }

}