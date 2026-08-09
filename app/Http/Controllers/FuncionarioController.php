<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\Presenca;
use App\Services\DiariaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FuncionarioController extends Controller
{
    public function index(Request $request): View
    {
        $filtro = $request->string('filtro', 'ativos')->toString();

        $funcionarios = Funcionario::query()
            ->when($filtro === 'ativos', fn ($q) => $q->where('ativo', true))
            ->when($filtro === 'inativos', fn ($q) => $q->where('ativo', false))
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->get();

        return view('funcionarios.index', compact('funcionarios', 'filtro'));
    }

    public function create(): View
    {
        return view('funcionarios.create');
    }

    public function store(Request $request, DiariaService $diarias): RedirectResponse
    {
        $dados = $this->validated($request);
        $dados['ativo'] = true;

        if ($dados['locomocao_tipo'] === 'nenhuma') {
            $dados['locomocao_valor'] = 0;
        }

        $funcionario = Funcionario::create($dados);
        $diarias->atualizarDiaria($funcionario, (float) $dados['diaria_atual'], now());

        return redirect()->route('funcionarios.index')->with('success', 'Funcionário cadastrado.');
    }

    public function show(Request $request, Funcionario $funcionario): View
    {
        $mes = $request->string('mes', now()->format('Y-m'))->toString();
        $inicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fim = (clone $inicio)->endOfMonth();

        $presencas = Presenca::query()
            ->with('obra')
            ->where('funcionario_id', $funcionario->id)
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->get()
            ->keyBy(fn (Presenca $p) => $p->data->format('Y-m-d'));

        $historico = $funcionario->diariaHistoricos()->limit(20)->get();

        return view('funcionarios.show', compact('funcionario', 'mes', 'inicio', 'fim', 'presencas', 'historico'));
    }

    public function edit(Funcionario $funcionario): View
    {
        return view('funcionarios.edit', compact('funcionario'));
    }

    public function update(Request $request, Funcionario $funcionario, DiariaService $diarias): RedirectResponse
    {
        $dados = $this->validated($request, true);
        $novaDiaria = (float) $dados['diaria_atual'];
        $diariaMudou = abs($novaDiaria - (float) $funcionario->diaria_atual) > 0.001;

        if ($dados['locomocao_tipo'] === 'nenhuma') {
            $dados['locomocao_valor'] = 0;
        }

        $funcionario->update([
            'nome' => $dados['nome'],
            'telefone' => $dados['telefone'] ?? null,
            'observacoes' => $dados['observacoes'] ?? null,
            'diaria_atual' => $novaDiaria,
            'locomocao_tipo' => $dados['locomocao_tipo'],
            'locomocao_valor' => $dados['locomocao_valor'],
        ]);

        if ($diariaMudou) {
            $diarias->atualizarDiaria(
                $funcionario,
                $novaDiaria,
                $dados['vigente_desde'] ?? now()->toDateString()
            );
        }

        return redirect()->route('funcionarios.show', $funcionario)->with('success', 'Funcionário atualizado.');
    }

    /**
     * Remove da equipe ativa sem apagar o histórico
     * (presenças, diárias, lançamentos e períodos).
     */
    public function destroy(Funcionario $funcionario): RedirectResponse
    {
        $funcionario->update(['ativo' => false]);

        return redirect()
            ->route('funcionarios.index', ['filtro' => 'inativos'])
            ->with('success', 'Funcionário removido da equipe. O histórico foi preservado.');
    }

    public function reativar(Funcionario $funcionario): RedirectResponse
    {
        $funcionario->update(['ativo' => true]);

        return redirect()
            ->route('funcionarios.show', $funcionario)
            ->with('success', 'Funcionário reativado na equipe.');
    }

    private function validated(Request $request, bool $comVigencia = false): array
    {
        $regras = [
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:50'],
            'diaria_atual' => ['required', 'numeric', 'min:0'],
            'locomocao_tipo' => ['required', Rule::in(array_keys(Funcionario::LOCOMOCAO_TIPOS))],
            'locomocao_valor' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string'],
        ];

        if ($comVigencia) {
            $regras['vigente_desde'] = ['nullable', 'date'];
        }

        $dados = $request->validate($regras);
        $dados['locomocao_valor'] = (float) ($dados['locomocao_valor'] ?? 0);

        return $dados;
    }
}
