<?php

namespace App\Http\Controllers;

use App\Models\PeriodoPagamento;
use App\Services\PeriodoService;
use Illuminate\View\View;

class ComprovantePeriodoController extends Controller
{
    public function __invoke(PeriodoPagamento $periodo, PeriodoService $service): View
    {
        $resumo = $service->resumo($periodo);
        $pagamentos = $periodo->pagamentos()
            ->with('funcionario')
            ->get()
            ->keyBy('funcionario_id');
        $lancamentos = $periodo->lancamentos()
            ->with('funcionario')
            ->orderBy('data')
            ->get();

        return view('periodos.comprovante', compact('periodo', 'resumo', 'pagamentos', 'lancamentos'));
    }
}
