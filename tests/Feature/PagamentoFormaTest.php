<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use App\Models\PagamentoFuncionario;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use App\Models\Obra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagamentoFormaTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_forma_de_pagamento_por_pessoa(): void
    {
        $user = User::factory()->create();
        $obra = Obra::create(['nome' => 'Obra A', 'ativa' => true]);
        $joao = Funcionario::create(['nome' => 'João', 'diaria_atual' => 150, 'ativo' => true]);
        $carlos = Funcionario::create(['nome' => 'Carlos', 'diaria_atual' => 140, 'ativo' => true]);

        $periodo = PeriodoPagamento::create([
            'nome' => 'Semana 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'fechado',
            'fechado_em' => now(),
        ]);

        Presenca::create([
            'funcionario_id' => $joao->id,
            'obra_id' => $obra->id,
            'data' => '2026-08-03',
            'tipo' => 'integral',
            'valor_aplicado' => 150,
            'locomocao_tipo' => 'nenhuma',
            'valor_locomocao' => 0,
        ]);

        Presenca::create([
            'funcionario_id' => $carlos->id,
            'obra_id' => $obra->id,
            'data' => '2026-08-03',
            'tipo' => 'integral',
            'valor_aplicado' => 140,
            'locomocao_tipo' => 'nenhuma',
            'valor_locomocao' => 0,
        ]);

        $this->actingAs($user)->post(route('periodos.pagar', $periodo), [
            'data_pagamento' => '2026-08-07',
            'pagamentos' => [
                $joao->id => ['forma' => 'dinheiro', 'observacao' => 'No canteiro'],
                $carlos->id => ['forma' => 'pix'],
            ],
        ])->assertRedirect(route('periodos.show', $periodo));

        $this->assertSame('pago', $periodo->fresh()->status);
        $this->assertDatabaseHas('pagamento_funcionarios', [
            'funcionario_id' => $joao->id,
            'forma' => 'dinheiro',
            'valor' => 150,
        ]);
        $this->assertDatabaseHas('pagamento_funcionarios', [
            'funcionario_id' => $carlos->id,
            'forma' => 'pix',
            'valor' => 140,
        ]);
    }
}
