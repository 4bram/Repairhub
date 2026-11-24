<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    const DELETED_AT = 'eliminado_en';

    protected $fillable = ['establecimiento_id','usuario_id', 'nombre', 'correo', 'telefono', 'observaciones'];

    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'cliente_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'cliente_id');
    }

}