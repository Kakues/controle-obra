<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\Obra;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use App\Services\DiariaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarcacaoRapidaController extends Controller
{
    public function create(Request $request): View
    {
        $data = $request->string('data', now()->toDateString())->toString();

        $funcionarios = Funcionario::query()
            ->where('ativo', true)
            ->where('regime', 'diaria')
            ->orderBy('nome')
            ->get();

        $obras = Obra::query()
            ->where('ativa', true)
            ->orderBy('nome')
            ->get();

        $presencas = Presenca::query()
            ->whereDate('data', $data)
            ->get()
            ->keyBy('funcionario_id');

        $periodoFechado = PeriodoPagamento::query()
            ->whereIn('status', ['fechado', 'pago'])
            ->whereDate('data_inicio', '<=', $data)
            ->whereDate('data_fim', '>=', $data)
            ->exists();

        $periodoAberto = PeriodoPagamento::query()
            ->where('status', 'aberto')
            ->whereDate('data_inicio', '<=', $data)
            ->whereDate('data_fim', '>=', $data)
            ->first();

        return view('presencas.marcacao', compact(
            'data',
            'funcionarios',
            'obras',
            'presencas',
            'periodoFechado',
            'periodoAberto'
        ));
    }

    public function store(Request $request, DiariaService $diarias): RedirectResponse
    {
        $dados = $request->validate([
            'data' => ['required', 'date'],
            'obra_padrao_id' => ['nullable', 'exists:obras,id'],
            'marcacoes' => ['nullable', 'array'],
            'marcacoes.*.presente' => ['nullable', 'boolean'],
            'marcacoes.*.obra_id' => ['nullable', 'exists:obras,id'],
            'marcacoes.*.tipo' => ['nullable', 'in:integral,meio,especial'],
            'marcacoes.*.valor_especial' => ['nullable', 'numeric', 'min:0'],
            'marcacoes.*.pagar_locomocao' => ['nullable', 'boolean'],
        ]);

        $data = $dados['data'];

        $periodoFechado = PeriodoPagamento::query()
            ->whereIn('status', ['fechado', 'pago'])
            ->whereDate('data_inicio', '<=', $data)
            ->whereDate('data_fim', '>=', $data)
            ->exists();

        if ($periodoFechado) {
            return back()->with('error', 'Este dia pertence a um período já fechado/pago e não pode ser alterado.');
        }

        $marcacoes = $dados['marcacoes'] ?? [];

        foreach ($marcacoes as $funcionarioId => $marcacao) {
            $funcionario = Funcionario::find($funcionarioId);
            if (! $funcionario || ! $funcionario->isDiaria()) {
                continue;
            }

            $presente = ! empty($marcacao['presente']);

            if (! $presente) {
                Presenca::query()
                    ->where('funcionario_id', $funcionarioId)
                    ->whereDate('data', $data)
                    ->delete();
                continue;
            }

            $obraId = $marcacao['obra_id'] ?? $dados['obra_padrao_id'] ?? null;
            if (! $obraId) {
                return back()->withInput()->with('error', "Selecione a obra para {$funcionario->nome}.");
            }

            $tipo = $marcacao['tipo'] ?? 'integral';
            $valorEspecial = isset($marcacao['valor_especial']) && $marcacao['valor_especial'] !== ''
                ? (float) $marcacao['valor_especial']
                : null;

            $valor = $diarias->calcularValorPresenca($funcionario, $data, $tipo, $valorEspecial);

            $pagarLocomocao = array_key_exists('pagar_locomocao', $marcacao)
                ? ! empty($marcacao['pagar_locomocao'])
                : $funcionario->temLocomocao();

            $locomocaoTipo = 'nenhuma';
            $valorLocomocao = 0.0;

            if ($pagarLocomocao && $funcionario->temLocomocao()) {
                $locomocaoTipo = $funcionario->locomocao_tipo;
                $valorLocomocao = (float) $funcionario->locomocao_valor;
            }

            Presenca::query()->updateOrCreate(
                [
                    'funcionario_id' => $funcionario->id,
                    'data' => $data,
                ],
                [
                    'obra_id' => $obraId,
                    'tipo' => $tipo,
                    'valor_aplicado' => $valor,
                    'locomocao_tipo' => $locomocaoTipo,
                    'valor_locomocao' => $valorLocomocao,
                    'registrado_por' => $request->user()->id,
                ]
            );
        }

        return redirect()
            ->route('presencas.marcacao', ['data' => $data])
            ->with('success', 'Presenças salvas.');
    }
}
