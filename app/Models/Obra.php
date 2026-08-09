<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obra extends Model
{
    protected $fillable = [
        'nome',
        'endereco',
        'ativa',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'ativa' => 'boolean',
        ];
    }

    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }
}
