<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\Lancamento;
use App\Models\PeriodoPagamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LancamentoController extends Controller
{
    public function index(Request $request): View
    {
        $periodos = PeriodoPagamento::query()
            ->orderByDesc('data_inicio')
            ->get();

        $filtroExplicito = $request->has('filtro');

        if (! $filtroExplicito) {
            $periodoId = $periodos->firstWhere('status', 'aberto')?->id;
            $tiposSelecionados = array_keys(Lancamento::TIPOS);
        } else {
            $periodoId = $request->filled('periodo_pagamento_id')
                ? $request->integer('periodo_pagamento_id')
                : null;
            $tiposSelecionados = $request->input('tipos', []);
        }

        $funcionarioId = $request->integer('funcionario_id') ?: null;
        $periodoFiltro = $periodoId ? $periodos->firstWhere('id', $periodoId) : null;

        $lancamentos = Lancamento::query()
            ->with(['funcionario', 'periodo', 'registradoPor'])
            ->when(
                $periodoFiltro,
                fn ($q) => $q->where(function ($q2) use ($periodoFiltro) {
                    $q2->where('periodo_pagamento_id', $periodoFiltro->id)
                        ->orWhere(function ($q3) use ($periodoFiltro) {
                            $q3->whereNull('periodo_pagamento_id')
                                ->whereBetween('data', [
                                    $periodoFiltro->data_inicio->toDateString(),
                                    $periodoFiltro->data_fim->toDateString(),
                                ]);
                        });
                })
            )
            ->when(
                ! empty($tiposSelecionados),
                fn ($q) => $q->whereIn('tipo', $tiposSelecionados),
                fn ($q) => $q->whereRaw('0 = 1')
            )
            ->when($funcionarioId, fn ($q) => $q->where('funcionario_id', $funcionarioId))
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $funcionarios = Funcionario::query()->orderBy('nome')->get();

        return view('lancamentos.index', compact(
            'lancamentos',
            'funcionarios',
            'periodos',
            'periodoId',
            'periodoFiltro',
            'tiposSelecionados',
            'funcionarioId',
            'filtroExplicito',
        ));
    }

    public function create(Request $request): View
    {
        return view('lancamentos.create', [
            'funcionarios' => Funcionario::query()->where('ativo', true)->orderBy('nome')->get(),
            'periodos' => PeriodoPagamento::query()->where('status', 'aberto')->orderByDesc('data_inicio')->get(),
            'tipoPadrao' => $request->string('tipo', 'adiantamento')->toString(),
            'funcionarioId' => $request->integer('funcionario_id') ?: null,
            'periodoId' => $request->integer('periodo_pagamento_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validated($request);

        if ($erro = $this->periodoBloqueado($dados['periodo_pagamento_id'] ?? null)) {
            return back()->withInput()->with('error', $erro);
        }

        Lancamento::create([
            ...$dados,
            'registrado_por' => $request->user()->id,
        ]);

        return redirect()
            ->route('lancamentos.index')
            ->with('success', 'Lançamento registrado.');
    }

    public function edit(Lancamento $lancamento): View|RedirectResponse
    {
        if ($erro = $this->periodoBloqueado($lancamento->periodo_pagamento_id)) {
            return redirect()->route('lancamentos.index')->with('error', $erro);
        }

        return view('lancamentos.edit', [
            'lancamento' => $lancamento,
            'funcionarios' => Funcionario::query()->orderBy('nome')->get(),
            'periodos' => PeriodoPagamento::query()
                ->where(function ($q) use ($lancamento) {
                    $q->where('status', 'aberto')
                        ->orWhere('id', $lancamento->periodo_pagamento_id);
                })
                ->orderByDesc('data_inicio')
                ->get(),
        ]);
    }

    public function update(Request $request, Lancamento $lancamento): RedirectResponse
    {
        if ($erro = $this->periodoBloqueado($lancamento->periodo_pagamento_id)) {
            return redirect()->route('lancamentos.index')->with('error', $erro);
        }

        $dados = $this->validated($request);

        if ($erro = $this->periodoBloqueado($dados['periodo_pagamento_id'] ?? null)) {
            return back()->withInput()->with('error', $erro);
        }

        $lancamento->update($dados);

        return redirect()
            ->route('lancamentos.index')
            ->with('success', 'Lançamento atualizado.');
    }

    public function destroy(Lancamento $lancamento): RedirectResponse
    {
        if ($erro = $this->periodoBloqueado($lancamento->periodo_pagamento_id)) {
            return back()->with('error', $erro);
        }

        $lancamento->delete();

        return redirect()
            ->route('lancamentos.index')
            ->with('success', 'Lançamento removido.');
    }

    private function validated(Request $request): array
    {
        $dados = $request->validate([
            'funcionario_id' => ['required', 'exists:funcionarios,id'],
            'periodo_pagamento_id' => ['nullable', 'exists:periodo_pagamentos,id'],
            'tipo' => ['required', Rule::in(array_keys(Lancamento::TIPOS))],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($dados['periodo_pagamento_id'])) {
            $dados['periodo_pagamento_id'] = PeriodoPagamento::query()
                ->where('status', 'aberto')
                ->whereDate('data_inicio', '<=', $dados['data'])
                ->whereDate('data_fim', '>=', $dados['data'])
                ->value('id');
        }

        return $dados;
    }

    private function periodoBloqueado(?int $periodoId): ?string
    {
        if (! $periodoId) {
            return null;
        }

        $periodo = PeriodoPagamento::query()->find($periodoId);

        if ($periodo && in_array($periodo->status, ['fechado', 'pago'], true)) {
            return 'Não é possível alterar lançamentos de um período fechado/pago.';
        }

        return null;
    }
}
