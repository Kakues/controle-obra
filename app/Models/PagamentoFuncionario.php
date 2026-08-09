<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagamentoFuncionario extends Model
{
    public const FORMAS = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'PIX',
        'transferencia' => 'Transferência',
        'outro' => 'Outro',
    ];

    protected $fillable = [
        'periodo_pagamento_id',
        'funcionario_id',
        'forma',
        'valor',
        'data_pagamento',
        'observacao',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_pagamento' => 'date',
        ];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoPagamento::class, 'periodo_pagamento_id');
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function labelForma(): string
    {
        return self::FORMAS[$this->forma] ?? $this->forma;
    }
}
