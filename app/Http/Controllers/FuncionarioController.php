<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\Presenca;
use App\Services\DiariaService;
use App\Services\EquipeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FuncionarioController extends Controller
{
    public function index(Request $request, EquipeService $equipes): View
    {
        $filtro = $request->string('filtro', 'ativos')->toString();

        $funcionarios = Funcionario::query()
            ->with(['superior', 'equipe'])
            ->when($filtro === 'ativos', fn ($q) => $q->where('ativo', true))
            ->when($filtro === 'inativos', fn ($q) => $q->where('ativo', false))
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->get();

        $blocos = $equipes->blocosMarcacao($funcionarios);

        return view('funcionarios.index', compact('blocos', 'filtro'));
    }

    public function create(): View
    {
        return view('funcionarios.create', [
            'lideres' => $this->lideresDisponiveis(),
        ]);
    }

    public function store(Request $request, DiariaService $diarias): RedirectResponse
    {
        $dados = $this->validated($request);
        $dados['ativo'] = true;

        if ($dados['locomocao_tipo'] === 'nenhuma') {
            $dados['locomocao_valor'] = 0;
        }

        if ($dados['regime'] === 'empreita') {
            $dados['diaria_atual'] = 0;
            $dados['locomocao_tipo'] = 'nenhuma';
            $dados['locomocao_valor'] = 0;
        }

        $funcionario = Funcionario::create($dados);

        if ($funcionario->isDiaria()) {
            $diarias->atualizarDiaria($funcionario, (float) $dados['diaria_atual'], now());
        }

        return redirect()->route('funcionarios.index')->with('success', 'Funcionário cadastrado.');
    }

    public function show(Request $request, Funcionario $funcionario): View
    {
        $funcionario->load(['superior', 'equipe']);

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

        $lancamentosRecentes = $funcionario->isEmpreita()
            ? $funcionario->lancamentos()->with('periodo')->orderByDesc('data')->limit(30)->get()
            : collect();

        return view('funcionarios.show', compact(
            'funcionario',
            'mes',
            'inicio',
            'fim',
            'presencas',
            'historico',
            'lancamentosRecentes'
        ));
    }

    public function edit(Funcionario $funcionario): View
    {
        return view('funcionarios.edit', [
            'funcionario' => $funcionario->load(['superior', 'equipe']),
            'lideres' => $this->lideresDisponiveis($funcionario),
        ]);
    }

    public function update(Request $request, Funcionario $funcionario, DiariaService $diarias): RedirectResponse
    {
        $dados = $this->validated($request, $funcionario);
        $novaDiaria = (float) $dados['diaria_atual'];

        if ($dados['locomocao_tipo'] === 'nenhuma') {
            $dados['locomocao_valor'] = 0;
        }

        if ($dados['regime'] === 'empreita') {
            $novaDiaria = 0;
            $dados['diaria_atual'] = 0;
            $dados['locomocao_tipo'] = 'nenhuma';
            $dados['locomocao_valor'] = 0;
        }

        $diariaMudou = $dados['regime'] === 'diaria'
            && abs($novaDiaria - (float) $funcionario->diaria_atual) > 0.001;

        $funcionario->update([
            'nome' => $dados['nome'],
            'regime' => $dados['regime'],
            'superior_id' => $dados['superior_id'] ?? null,
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

    public function destroy(Funcionario $funcionario): RedirectResponse
    {
        $funcionario->equipe()->update(['superior_id' => null]);
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

    private function lideresDisponiveis(?Funcionario $excluir = null)
    {
        return Funcionario::query()
            ->where('ativo', true)
            ->whereNull('superior_id')
            ->when($excluir, fn ($q) => $q->where('id', '!=', $excluir->id))
            ->orderBy('nome')
            ->get();
    }

    private function validated(Request $request, ?Funcionario $funcionario = null, bool $comVigencia = false): array
    {
        $regras = [
            'nome' => ['required', 'string', 'max:255'],
            'regime' => ['required', Rule::in(array_keys(Funcionario::REGIMES))],
            'superior_id' => ['nullable', 'exists:funcionarios,id'],
            'telefone' => ['nullable', 'string', 'max:50'],
            'diaria_atual' => ['nullable', 'numeric', 'min:0', 'required_if:regime,diaria'],
            'locomocao_tipo' => ['required', Rule::in(array_keys(Funcionario::LOCOMOCAO_TIPOS))],
            'locomocao_valor' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string'],
        ];

        if ($comVigencia || $funcionario) {
            $regras['vigente_desde'] = ['nullable', 'date'];
        }

        $dados = $request->validate($regras);
        $dados['diaria_atual'] = (float) ($dados['diaria_atual'] ?? 0);
        $dados['locomocao_valor'] = (float) ($dados['locomocao_valor'] ?? 0);
        $dados['superior_id'] = $dados['superior_id'] ?? null;

        $this->validarHierarquia($dados['superior_id'], $funcionario);

        return $dados;
    }

    private function validarHierarquia(?int $superiorId, ?Funcionario $funcionario): void
    {
        if (! $superiorId) {
            return;
        }

        if ($funcionario && $superiorId === $funcionario->id) {
            throw ValidationException::withMessages([
                'superior_id' => 'A pessoa não pode ser líder de si mesma.',
            ]);
        }

        $superior = Funcionario::query()->find($superiorId);

        if (! $superior || ! $superior->ativo) {
            throw ValidationException::withMessages([
                'superior_id' => 'Selecione um líder ativo.',
            ]);
        }

        if ($superior->superior_id) {
            throw ValidationException::withMessages([
                'superior_id' => 'Só é permitido um nível de equipe (líder direto com você).',
            ]);
        }

        if ($funcionario && $funcionario->equipe()->exists()) {
            throw ValidationException::withMessages([
                'superior_id' => 'Quem lidera uma equipe não pode ter outro líder.',
            ]);
        }
    }
}
