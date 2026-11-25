<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seguimiento extends Model
{
    protected $table = 'seguimientos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = ['orden_id', 'usuario_id', 'estado', 'descripcion', 'visible_cliente'];

    protected function casts(): array
    {
        return ['visible_cliente' => 'boolean'];
    }

    public function evidencias()
    {
        return $this->hasMany(Evidencia::class, 'seguimiento_id');
    }

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }
}