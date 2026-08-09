<?php

namespace App\Services;

use App\Models\Presenca;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ObraCustoService
{
    /**
     * @return Collection<int, array{
     *   obra_id: int|null,
     *   obra_nome: string,
     *   dias: int,
     *   total_diarias: float,
     *   total_locomocao: float,
     *   total: float
     * }>
     */
    public function resumir(string $inicio, string $fim): Collection
    {
        $inicio = Carbon::parse($inicio)->toDateString();
        $fim = Carbon::parse($fim)->toDateString();

        $presencas = Presenca::query()
            ->with('obra')
            ->whereBetween('data', [$inicio, $fim])
            ->get()
            ->groupBy('obra_id');

        return $presencas->map(function (Collection $lista, $obraId) {
            $obra = $lista->first()?->obra;

            return [
                'obra_id' => $obraId ?: null,
                'obra_nome' => $obra?->nome ?? 'Sem obra',
                'dias' => $lista->count(),
                'total_diarias' => (float) $lista->sum('valor_aplicado'),
                'total_locomocao' => (float) $lista->sum('valor_locomocao'),
                'total' => round((float) $lista->sum('valor_aplicado') + (float) $lista->sum('valor_locomocao'), 2),
            ];
        })->sortByDesc('total')->values();
    }
}
