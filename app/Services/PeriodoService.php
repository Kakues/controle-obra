<?php

namespace App\Services;

use App\Models\Funcionario;
use App\Models\Lancamento;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use Illuminate\Support\Collection;

class PeriodoService
{
    /**
     * @return Collection<int, array{
     *   funcionario: Funcionario,
     *   dias: int,
     *   total_diarias: float,
     *   total_locomocao: float,
     *   adiantamentos: float,
     *   descontos: float,
     *   bonus: float,
     *   a_pagar: float,
     *   presencas: Collection
     * }>
     */
    public function resumo(PeriodoPagamento $periodo): Collection
    {
        [$inicio, $fim] = $this->intervalo($periodo);

        $presencas = Presenca::query()
            ->with(['funcionario', 'obra'])
            ->whereBetween('data', [$inicio, $fim])
            ->orderBy('data')
            ->get()
            ->groupBy('funcionario_id');

        $lancamentos = Lancamento::query()
            ->where(function ($q) use ($periodo, $inicio, $fim) {
                $q->where('periodo_pagamento_id', $periodo->id)
                    ->orWhere(function ($q2) use ($inicio, $fim) {
                        $q2->whereNull('periodo_pagamento_id')
                            ->whereBetween('data', [$inicio, $fim]);
                    });
            })
            ->get()
            ->groupBy('funcionario_id');

        $funcionarioIds = $presencas->keys()->merge($lancamentos->keys())->unique();

        return $funcionarioIds->map(function ($funcionarioId) use ($presencas, $lancamentos) {
            /** @var Collection $listaPresencas */
            $listaPresencas = $presencas->get($funcionarioId, collect());
            /** @var Collection $listaLancamentos */
            $listaLancamentos = $lancamentos->get($funcionarioId, collect());

            $funcionario = $listaPresencas->first()?->funcionario
                ?? $listaLancamentos->first()?->funcionario
                ?? Funcionario::find($funcionarioId);

            $totalDiarias = (float) $listaPresencas->sum('valor_aplicado');
            $totalLocomocao = (float) $listaPresencas->sum('valor_locomocao');
            $adiantamentos = (float) $listaLancamentos->where('tipo', 'adiantamento')->sum('valor');
            $descontos = (float) $listaLancamentos->where('tipo', 'desconto')->sum('valor');
            $bonus = (float) $listaLancamentos->where('tipo', 'bonus')->sum('valor');

            return [
                'funcionario' => $funcionario,
                'dias' => $listaPresencas->count(),
                'total_diarias' => $totalDiarias,
                'total_locomocao' => $totalLocomocao,
                'adiantamentos' => $adiantamentos,
                'descontos' => $descontos,
                'bonus' => $bonus,
                'a_pagar' => round($totalDiarias + $totalLocomocao + $bonus - $adiantamentos - $descontos, 2),
                'presencas' => $listaPresencas,
            ];
        })->sortBy(fn ($item) => $item['funcionario']?->nome)->values();
    }

    /**
     * Quantas presenças já existem no intervalo (mesmo antes de criar o período).
     */
    public function contarPresencasNoIntervalo(string $inicio, string $fim): int
    {
        return Presenca::query()
            ->whereBetween('data', [$inicio, $fim])
            ->count();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function intervalo(PeriodoPagamento $periodo): array
    {
        return [
            $periodo->data_inicio->toDateString(),
            $periodo->data_fim->toDateString(),
        ];
    }
}
