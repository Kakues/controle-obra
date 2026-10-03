<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Funcionario; use App\Models\Presenca; use App\Models\Lancamento; use App\Models\PagamentoFuncionario; use App\Models\PeriodoPagamento; use Carbon\Carbon;
$leo = Funcionario::where('nome','Leo')->first();
$inicio = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
$fim = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();
$ids = [$leo->id];
$children = Funcionario::where('superior_id',$leo->id)->pluck('id')->all();
$ids = array_merge($ids,$children);
$periodoIds = PeriodoPagamento::where('data_inicio','<=',$fim)->where('data_fim','>=',$inicio)->pluck('id')->all();
echo json_encode([
 'ids'=>$ids,
 'equipe'=>Funcionario::whereIn('id',$ids)->get(['id','nome','superior_id'])->toArray(),
 'presencas_week'=>Presenca::whereIn('funcionario_id',$ids)->whereBetween('data',[$inicio,$fim])->count(),
 'lancamentos_week_or_period'=>Lancamento::whereIn('funcionario_id',$ids)->where(function($q)use($inicio,$fim,$periodoIds){$q->whereBetween('data',[$inicio,$fim]);if($periodoIds)$q->orWhereIn('periodo_pagamento_id',$periodoIds);})->count(),
 'pagamentos_period'=>PagamentoFuncionario::whereIn('funcionario_id',$ids)->whereIn('periodo_pagamento_id',$periodoIds)->count(),
 'all_lanc'=>Lancamento::whereIn('funcionario_id',$ids)->count(),
 'all_pres'=>Presenca::whereIn('funcionario_id',$ids)->count(),
], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
