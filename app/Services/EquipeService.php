<?php

namespace App\Services;

use App\Models\Funcionario;
use Illuminate\Support\Collection;

class EquipeService
{
    /**
     * @return Collection<int, array{lider: Funcionario, membros: Collection<int, Funcionario>}>
     */
    public function blocosMarcacao(Collection $funcionarios): Collection
    {
        $equipes = $funcionarios->whereNotNull('superior_id')->groupBy('superior_id');
        $solos = $funcionarios->whereNull('superior_id')->sortBy('nome')->values();

        $lideresForaDaLista = Funcionario::query()
            ->whereIn('id', $equipes->keys())
            ->whereNotIn('id', $solos->pluck('id'))
            ->get()
            ->keyBy('id');

        $blocos = collect();

        foreach ($solos as $lider) {
            $blocos->push([
                'lider' => $lider,
                'membros' => $equipes->get($lider->id, collect())->sortBy('nome')->values(),
            ]);
            $equipes->forget($lider->id);
        }

        foreach ($equipes as $liderId => $membros) {
            $lider = $lideresForaDaLista->get($liderId) ?? Funcionario::find($liderId);
            if (! $lider) {
                continue;
            }

            $blocos->push([
                'lider' => $lider,
                'membros' => $membros->sortBy('nome')->values(),
            ]);
        }

        return $blocos;
    }

    /**
     * @return array{
     *   diretos: Collection<int, Funcionario>,
     *   equipes: Collection<int, array{lider: Funcionario, membros: Collection<int, Funcionario>}>
     * }
     */
    public function marcacaoAgrupada(Collection $funcionarios): array
    {
        $blocos = $this->blocosMarcacao($funcionarios);

        return [
            'diretos' => $blocos
                ->filter(fn ($bloco) => $bloco['membros']->isEmpty())
                ->map(fn ($bloco) => $bloco['lider'])
                ->filter(fn (Funcionario $funcionario) => $funcionario->isDiaria())
                ->sortBy('nome')
                ->values(),
            'equipes' => $blocos
                ->filter(fn ($bloco) => $bloco['membros']->isNotEmpty())
                ->sortBy(fn ($bloco) => $bloco['lider']->nome)
                ->values(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $resumo
     * @return array{
     *   blocos: Collection<int, array{lider: ?Funcionario, lider_item: ?array, membros: Collection, total_equipe: float}>,
     *   solos: Collection<int, array<string, mixed>>
     * }
     */
    public function blocosResumo(Collection $resumo): array
    {
        $porFuncionario = $resumo->keyBy(fn ($item) => $item['funcionario']?->id);

        $equipes = $resumo
            ->filter(fn ($item) => $item['funcionario']?->superior_id)
            ->groupBy(fn ($item) => $item['funcionario']->superior_id);

        $solos = $resumo
            ->filter(fn ($item) => ! $item['funcionario']?->superior_id)
            ->values();

        $blocos = collect();

        foreach ($solos as $liderItem) {
            $liderId = $liderItem['funcionario']?->id;
            $membros = $equipes->get($liderId, collect());

            if ($membros->isNotEmpty()) {
                $blocos->push([
                    'lider' => $liderItem['funcionario'],
                    'lider_item' => $liderItem,
                    'membros' => $membros->values(),
                    'total_equipe' => round(
                        (float) $liderItem['a_pagar'] + (float) $membros->sum('a_pagar'),
                        2
                    ),
                ]);
                $equipes->forget($liderId);
            }
        }

        foreach ($equipes as $liderId => $membros) {
            $lider = Funcionario::find($liderId);
            $liderItem = $porFuncionario->get($liderId);

            $blocos->push([
                'lider' => $lider,
                'lider_item' => $liderItem,
                'membros' => $membros->values(),
                'total_equipe' => round(
                    (float) ($liderItem['a_pagar'] ?? 0) + (float) $membros->sum('a_pagar'),
                    2
                ),
            ]);
        }

        $solosSemEquipe = $solos->filter(function ($item) use ($blocos) {
            $liderId = $item['funcionario']?->id;

            return ! $blocos->contains(fn ($bloco) => $bloco['lider']?->id === $liderId);
        })->values();

        return [
            'blocos' => $blocos,
            'solos' => $solosSemEquipe,
        ];
    }

    /**
     * @return Collection<int, array{funcionario: Funcionario, nivel: int}>
     */
    public function listaHierarquica(Collection $funcionarios): Collection
    {
        $funcionarios = $funcionarios->loadMissing(['superior', 'equipe']);

        $lista = collect();
        $processados = collect();

        foreach ($funcionarios->whereNull('superior_id')->sortBy('nome') as $lider) {
            $lista->push(['funcionario' => $lider, 'nivel' => 0]);
            $processados->push($lider->id);

            foreach ($funcionarios->where('superior_id', $lider->id)->sortBy('nome') as $membro) {
                $lista->push(['funcionario' => $membro, 'nivel' => 1]);
                $processados->push($membro->id);
            }
        }

        foreach ($funcionarios->whereNotIn('id', $processados)->sortBy('nome') as $orfao) {
            $lista->push(['funcionario' => $orfao, 'nivel' => 0]);
        }

        return $lista;
    }
}
