<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiariaHistorico extends Model
{
    protected $fillable = [
        'funcionario_id',
        'valor',
        'vigente_desde',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'vigente_desde' => 'date',
        ];
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }
}
