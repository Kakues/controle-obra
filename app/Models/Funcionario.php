<?php

namespace App\Models;

use App\Services\DiariaService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Funcionario extends Model
{
    public const REGIMES = [
        'diaria' => 'Diária',
        'empreita' => 'Empreita',
    ];

    public const LOCOMOCAO_TIPOS = [
        'nenhuma' => 'Sem auxílio',
        'onibus' => 'Ônibus / passagem',
        'gasolina' => 'Gasolina',
    ];

    protected $fillable = [
        'nome',
        'regime',
        'superior_id',
        'telefone',
        'diaria_atual',
        'locomocao_tipo',
        'locomocao_valor',
        'ativo',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'diaria_atual' => 'decimal:2',
            'locomocao_valor' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function superior(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superior_id');
    }

    public function equipe(): HasMany
    {
        return $this->hasMany(self::class, 'superior_id')->orderBy('nome');
    }

    public function diariaHistoricos(): HasMany
    {
        return $this->hasMany(DiariaHistorico::class)->orderByDesc('vigente_desde');
    }

    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }

    public function diariaEm(CarbonInterface|string $data): float
    {
        return app(DiariaService::class)->valorEm($this, $data);
    }

    public function temLocomocao(): bool
    {
        return $this->locomocao_tipo !== 'nenhuma' && (float) $this->locomocao_valor > 0;
    }

    public function labelLocomocao(): string
    {
        return self::LOCOMOCAO_TIPOS[$this->locomocao_tipo] ?? $this->locomocao_tipo;
    }

    public function isDiaria(): bool
    {
        return ($this->regime ?? 'diaria') === 'diaria';
    }

    public function isEmpreita(): bool
    {
        return $this->regime === 'empreita';
    }

    public function labelRegime(): string
    {
        return self::REGIMES[$this->regime ?? 'diaria'] ?? $this->regime;
    }

    public function temSuperior(): bool
    {
        return $this->superior_id !== null;
    }

    public function temEquipe(): bool
    {
        if ($this->relationLoaded('equipe')) {
            return $this->equipe->isNotEmpty();
        }

        return $this->equipe()->exists();
    }

    public function labelVinculo(): ?string
    {
        if ($this->temSuperior()) {
            return 'Equipe de '.$this->superior?->nome;
        }

        if ($this->temEquipe()) {
            return 'Líder de equipe';
        }

        return null;
    }
}
