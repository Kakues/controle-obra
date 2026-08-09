<?php

namespace App\Http\Controllers;

use App\Services\ObraCustoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelatorioObraController extends Controller
{
    public function __invoke(Request $request, ObraCustoService $service): View
    {
        $inicio = $request->string('inicio', now()->startOfMonth()->toDateString())->toString();
        $fim = $request->string('fim', now()->toDateString())->toString();

        $resumo = $service->resumir($inicio, $fim);

        return view('relatorios.obras', compact('resumo', 'inicio', 'fim'));
    }
}
