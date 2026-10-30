<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaOAuth extends Model
{
    protected $table = 'cuentas_oauth';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'proveedor',
        'proveedor_usuario_id',
        'correo_proveedor',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            Usuario::class,
            'usuario_id'
        );
    }
}
