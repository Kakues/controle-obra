<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lancamento extends Model
{
    public const TIPOS = [
        'empreita' => 'Valor empreita',
        'adiantamento' => 'Adiantamento',
        'desconto' => 'Desconto',
        'bonus' => 'Bônus',
    ];

    protected $fillable = [
        'funcionario_id',
        'periodo_pagamento_id',
        'tipo',
        'valor',
        'data',
        'descricao',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data' => 'date',
        ];
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoPagamento::class, 'periodo_pagamento_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
