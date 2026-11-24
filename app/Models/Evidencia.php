<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidencia extends Model
{
    protected $table = 'evidencias';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'orden_id',
        'seguimiento_id',
        'usuario_id',
        'ruta_archivo',
        'nombre_original',
        'tipo_mime',
        'tamano_bytes',
        'descripcion',
        'visible_cliente',
    ];

    protected $hidden = [
        'ruta_archivo',
    ];

    protected function casts(): array
    {
        return [
            'visible_cliente' => 'boolean',
            'tamano_bytes' => 'integer',
        ];
    }

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    public function seguimiento()
    {
        return $this->belongsTo(Seguimiento::class, 'seguimiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}