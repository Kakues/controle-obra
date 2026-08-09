<?php

namespace App\Services;

use App\Models\DiariaHistorico;
use App\Models\Funcionario;
use App\Models\Lancamento;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DiariaService
{
    public function valorEm(Funcionario $funcionario, CarbonInterface|string $data): float
    {
        $data = Carbon::parse($data)->startOfDay();

        $historico = DiariaHistorico::query()
            ->where('funcionario_id', $funcionario->id)
            ->whereDate('vigente_desde', '<=', $data)
            ->orderByDesc('vigente_desde')
            ->first();

        if ($historico) {
            return (float) $historico->valor;
        }

        return (float) $funcionario->diaria_atual;
    }

    public function atualizarDiaria(Funcionario $funcionario, float $valor, CarbonInterface|string $vigenteDesde): void
    {
        $vigenteDesde = Carbon::parse($vigenteDesde)->toDateString();

        DB::transaction(function () use ($funcionario, $valor, $vigenteDesde) {
            DiariaHistorico::query()->updateOrCreate(
                [
                    'funcionario_id' => $funcionario->id,
                    'vigente_desde' => $vigenteDesde,
                ],
                ['valor' => $valor]
            );

            $funcionario->update(['diaria_atual' => $valor]);
        });
    }

    public function calcularValorPresenca(Funcionario $funcionario, string $data, string $tipo, ?float $valorEspecial = null): float
    {
        if ($tipo === 'especial' && $valorEspecial !== null) {
            return round($valorEspecial, 2);
        }

        $base = $this->valorEm($funcionario, $data);

        return round($base * Presenca::multiplicador($tipo), 2);
    }
}
