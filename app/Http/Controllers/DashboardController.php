<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\Obra;
use App\Models\PeriodoPagamento;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'totalFuncionarios' => Funcionario::query()->where('ativo', true)->count(),
            'totalObras' => Obra::query()->where('ativa', true)->count(),
            'periodosAbertos' => PeriodoPagamento::query()->where('status', 'aberto')->count(),
        ]);
    }
}
