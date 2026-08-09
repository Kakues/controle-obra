<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoPagamento extends Model
{
    public const STATUS = [
        'aberto' => 'Aberto',
        'fechado' => 'Fechado',
        'pago' => 'Pago',
    ];

    protected $fillable = [
        'nome',
        'data_inicio',
        'data_fim',
        'status',
        'fechado_em',
        'pago_em',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'fechado_em' => 'datetime',
            'pago_em' => 'datetime',
        ];
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoFuncionario::class);
    }

    public function estaAberto(): bool
    {
        return $this->status === 'aberto';
    }
}
