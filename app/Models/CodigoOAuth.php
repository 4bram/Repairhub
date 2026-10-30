<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodigoOAuth extends Model
{
    protected $table = 'codigos_oauth';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'codigo_hash',
        'expira_en',
        'utilizado_en',
    ];

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'utilizado_en' => 'datetime',
            'creado_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
