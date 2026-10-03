<?php

namespace App\Http\Controllers;

use App\Models\PagamentoFuncionario;
use App\Models\PeriodoPagamento;
use App\Services\EquipeService;
use App\Services\PeriodoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodoPagamentoController extends Controller
{
    public function index(): View
    {
        $periodos = PeriodoPagamento::query()
            ->orderByDesc('data_inicio')
            ->get();

        return view('periodos.index', compact('periodos'));
    }

    public function create(): View
    {
        return view('periodos.create');
    }

    public function store(Request $request, PeriodoService $service): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $periodo = PeriodoPagamento::create([
            ...$dados,
            'status' => 'aberto',
        ]);

        $presencasExistentes = $service->contarPresencasNoIntervalo(
            $periodo->data_inicio->toDateString(),
            $periodo->data_fim->toDateString()
        );

        $mensagem = 'Período criado.';
        if ($presencasExistentes > 0) {
            $mensagem .= " {$presencasExistentes} presença(s) já lançada(s) neste intervalo entraram automaticamente.";
        }

        return redirect()
            ->route('periodos.show', $periodo)
            ->with('success', $mensagem);
    }

    public function show(PeriodoPagamento $periodo, PeriodoService $service, EquipeService $equipes): View
    {
        $resumo = $service->resumo($periodo);
        $agrupado = $equipes->blocosResumo($resumo);
        $lancamentos = $periodo->lancamentos()
            ->with('funcionario')
            ->orderByDesc('data')
            ->get();
        $pagamentos = $periodo->pagamentos()
            ->with('funcionario')
            ->get()
            ->keyBy('funcionario_id');

        return view('periodos.show', compact('periodo', 'resumo', 'agrupado', 'lancamentos', 'pagamentos'));
    }

    public function fechar(PeriodoPagamento $periodo): RedirectResponse
    {
        if (! $periodo->estaAberto()) {
            return back()->with('error', 'Só é possível fechar períodos abertos.');
        }

        $periodo->update([
            'status' => 'fechado',
            'fechado_em' => now(),
        ]);

        return back()->with('success', 'Período fechado. As presenças deste intervalo ficam bloqueadas.');
    }

    public function formularioPagar(PeriodoPagamento $periodo, PeriodoService $service): View|RedirectResponse
    {
        if ($periodo->status !== 'fechado') {
            return redirect()
                ->route('periodos.show', $periodo)
                ->with('error', 'Feche o período antes de registrar os pagamentos.');
        }

        $resumo = $service->resumo($periodo);

        if ($resumo->isEmpty()) {
            return redirect()
                ->route('periodos.show', $periodo)
                ->with('error', 'Não há valores a pagar neste período.');
        }

        return view('periodos.pagar', compact('periodo', 'resumo'));
    }

    public function pagar(Request $request, PeriodoPagamento $periodo, PeriodoService $service): RedirectResponse
    {
        if ($periodo->status !== 'fechado') {
            return back()->with('error', 'Feche o período antes de marcar como pago.');
        }

        $resumo = $service->resumo($periodo)->keyBy(fn ($item) => $item['funcionario']->id);

        $dados = $request->validate([
            'data_pagamento' => ['required', 'date'],
            'pagamentos' => ['required', 'array'],
            'pagamentos.*.forma' => ['required', Rule::in(array_keys(PagamentoFuncionario::FORMAS))],
            'pagamentos.*.observacao' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($resumo as $funcionarioId => $item) {
            if (! isset($dados['pagamentos'][$funcionarioId]['forma'])) {
                return back()->withInput()->with('error', "Informe a forma de pagamento de {$item['funcionario']->nome}.");
            }
        }

        DB::transaction(function () use ($periodo, $resumo, $dados, $request) {
            $periodo->pagamentos()->delete();

            foreach ($resumo as $funcionarioId => $item) {
                $pagamento = $dados['pagamentos'][$funcionarioId];

                PagamentoFuncionario::create([
                    'periodo_pagamento_id' => $periodo->id,
                    'funcionario_id' => $funcionarioId,
                    'forma' => $pagamento['forma'],
                    'valor' => $item['a_pagar'],
                    'data_pagamento' => $dados['data_pagamento'],
                    'observacao' => $pagamento['observacao'] ?? null,
                    'registrado_por' => $request->user()->id,
                ]);
            }

            $periodo->update([
                'status' => 'pago',
                'pago_em' => now(),
            ]);
        });

        return redirect()
            ->route('periodos.show', $periodo)
            ->with('success', 'Pagamentos registrados e período marcado como pago.');
    }

    public function reabrir(PeriodoPagamento $periodo): RedirectResponse
    {
        DB::transaction(function () use ($periodo) {
            $periodo->pagamentos()->delete();

            $periodo->update([
                'status' => 'aberto',
                'fechado_em' => null,
                'pago_em' => null,
            ]);
        });

        return back()->with('success', 'Período reaberto. Os registros de forma de pagamento foram limpos.');
    }

    public function destroy(PeriodoPagamento $periodo): RedirectResponse
    {
        if ($periodo->status === 'pago') {
            return back()->with('error', 'Não é possível excluir um período já pago.');
        }

        $periodo->delete();

        return redirect()->route('periodos.index')->with('success', 'Período removido.');
    }
}
