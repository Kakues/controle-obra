<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Funcionario;
use App\Models\Lancamento;
use App\Models\PagamentoFuncionario;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use App\Models\DiariaHistorico;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function collectTeamIds(int $rootId): array
{
    $ids = [$rootId];
    $queue = [$rootId];
    while ($queue) {
        $parentId = array_shift($queue);
        $children = Funcionario::where('superior_id', $parentId)->pluck('id')->all();
        foreach ($children as $childId) {
            if (! in_array($childId, $ids, true)) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }
    }
    return $ids;
}

$leo = Funcionario::where('nome', 'Leo')->first();
if (! $leo) {
    echo "Leo nao encontrado.\n";
    exit(1);
}

$inicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
$fimSemana = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

$ids = collectTeamIds($leo->id);
$funcionarios = Funcionario::whereIn('id', $ids)->orderBy('nome')->get(['id', 'nome', 'superior_id']);

$periodoIds = PeriodoPagamento::query()
    ->where('data_inicio', '<=', $fimSemana)
    ->where('data_fim', '>=', $inicioSemana)
    ->pluck('id')
    ->all();

$report = [
    'leo_id' => $leo->id,
    'semana' => $inicioSemana->toDateString().' a '.$fimSemana->toDateString(),
    'funcionarios' => $funcionarios->toArray(),
    'periodo_ids_semana' => $periodoIds,
    'deleted' => [],
];

DB::transaction(function () use ($ids, $inicioSemana, $fimSemana, $periodoIds, $leo, &$report) {
    $presencas = Presenca::whereIn('funcionario_id', $ids)
        ->whereBetween('data', [$inicioSemana, $fimSemana]);
    $report['deleted']['presencas'] = $presencas->count();
    $presencas->delete();

    $lancamentos = Lancamento::whereIn('funcionario_id', $ids)->where(function ($q) use ($inicioSemana, $fimSemana, $periodoIds) {
        $q->whereBetween('data', [$inicioSemana, $fimSemana]);
        if ($periodoIds !== []) {
            $q->orWhereIn('periodo_pagamento_id', $periodoIds);
        }
    });
    $report['deleted']['lancamentos'] = $lancamentos->count();
    $lancamentos->delete();

    $pagamentos = PagamentoFuncionario::whereIn('funcionario_id', $ids)
        ->whereIn('periodo_pagamento_id', $periodoIds);
    $report['deleted']['pagamento_funcionarios'] = $pagamentos->count();
    $pagamentos->delete();

    $historicos = DiariaHistorico::whereIn('funcionario_id', $ids);
    $report['deleted']['diaria_historicos'] = $historicos->count();
    $historicos->delete();

    $remainingLanc = Lancamento::whereIn('funcionario_id', $ids)->count();
    $remainingPag = PagamentoFuncionario::whereIn('funcionario_id', $ids)->count();
    $remainingPres = Presenca::whereIn('funcionario_id', $ids)->count();
    if ($remainingLanc > 0 || $remainingPag > 0 || $remainingPres > 0) {
        Lancamento::whereIn('funcionario_id', $ids)->delete();
        PagamentoFuncionario::whereIn('funcionario_id', $ids)->delete();
        Presenca::whereIn('funcionario_id', $ids)->delete();
        $report['deleted']['cleanup_extra'] = [
            'lancamentos' => $remainingLanc,
            'pagamentos' => $remainingPag,
            'presencas' => $remainingPres,
        ];
    }

    $childIds = array_values(array_filter($ids, fn ($id) => $id !== $leo->id));
    $report['deleted']['funcionarios_equipe'] = Funcionario::whereIn('id', $childIds)->count();
    Funcionario::whereIn('id', $childIds)->delete();

    $report['deleted']['funcionario_leo'] = Funcionario::where('id', $leo->id)->exists() ? 1 : 0;
    Funcionario::where('id', $leo->id)->delete();
});

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
