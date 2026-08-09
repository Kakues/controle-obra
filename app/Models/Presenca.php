<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presenca extends Model
{
    public const TIPOS = [
        'integral' => 'Dia integral',
        'meio' => 'Meio período',
        'especial' => 'Valor especial',
    ];

    protected $fillable = [
        'funcionario_id',
        'obra_id',
        'data',
        'tipo',
        'valor_aplicado',
        'locomocao_tipo',
        'valor_locomocao',
        'observacao',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'valor_aplicado' => 'decimal:2',
            'valor_locomocao' => 'decimal:2',
        ];
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public static function multiplicador(string $tipo): float
    {
        return match ($tipo) {
            'meio' => 0.5,
            'integral', 'especial' => 1.0,
            default => 1.0,
        };
    }
}
