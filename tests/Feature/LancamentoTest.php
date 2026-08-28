<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use App\Models\Lancamento;
use App\Models\PeriodoPagamento;
use App\Models\User;
use App\Services\PeriodoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LancamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_registrar_adiantamento_vinculado_ao_periodo(): void
    {
        $user = User::factory()->create();
        $funcionario = Funcionario::create([
            'nome' => 'João',
            'diaria_atual' => 150,
            'ativo' => true,
        ]);
        $periodo = PeriodoPagamento::create([
            'nome' => 'Quinzena 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-15',
            'status' => 'aberto',
        ]);

        $this->actingAs($user)->post(route('lancamentos.store'), [
            'funcionario_id' => $funcionario->id,
            'tipo' => 'adiantamento',
            'valor' => 50,
            'data' => '2026-08-05',
            'descricao' => 'Vale',
        ])->assertRedirect(route('lancamentos.index'));

        $lancamento = Lancamento::query()->first();

        $this->assertNotNull($lancamento);
        $this->assertSame('adiantamento', $lancamento->tipo);
        $this->assertEquals($periodo->id, $lancamento->periodo_pagamento_id);
        $this->assertEquals(50.0, (float) $lancamento->valor);
    }

    public function test_desconto_e_bonus_entram_no_resumo_do_periodo(): void
    {
        $funcionario = Funcionario::create([
            'nome' => 'Carlos',
            'diaria_atual' => 140,
            'ativo' => true,
        ]);
        $periodo = PeriodoPagamento::create([
            'nome' => 'Quinzena 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-15',
            'status' => 'aberto',
        ]);

        Lancamento::create([
            'funcionario_id' => $funcionario->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'desconto',
            'valor' => 20,
            'data' => '2026-08-03',
            'descricao' => 'Ferramenta',
        ]);

        Lancamento::create([
            'funcionario_id' => $funcionario->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'bonus',
            'valor' => 30,
            'data' => '2026-08-04',
            'descricao' => 'Extra',
        ]);

        $resumo = app(PeriodoService::class)->resumo($periodo);
        $item = $resumo->first();

        $this->assertEquals(20.0, $item['descontos']);
        $this->assertEquals(30.0, $item['bonus']);
        $this->assertEquals(10.0, $item['a_pagar']);
        $this->assertCount(2, $item['lancamentos']);
    }

    public function test_lancamentos_do_dia_ficam_disponiveis_no_resumo(): void
    {
        $funcionario = Funcionario::create([
            'nome' => 'Maria',
            'diaria_atual' => 100,
            'ativo' => true,
        ]);
        $periodo = PeriodoPagamento::create([
            'nome' => 'Semana 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'aberto',
        ]);

        $desconto = Lancamento::create([
            'funcionario_id' => $funcionario->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'desconto',
            'valor' => 15,
            'data' => '2026-08-05',
            'descricao' => 'Material',
        ]);

        $item = app(PeriodoService::class)->resumo($periodo)->first();

        $this->assertTrue($item['lancamentos']->contains('id', $desconto->id));
        $this->assertTrue($item['lancamentos']->first()->data->isSameDay('2026-08-05'));
    }

    public function test_index_filtra_por_periodo_aberto_e_tipos_por_padrao(): void
    {
        $user = User::factory()->create();
        $funcionario = Funcionario::create([
            'nome' => 'Ana',
            'diaria_atual' => 120,
            'ativo' => true,
        ]);
        $periodoAberto = PeriodoPagamento::create([
            'nome' => 'Semana atual',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'aberto',
        ]);
        $periodoAntigo = PeriodoPagamento::create([
            'nome' => 'Semana passada',
            'data_inicio' => '2026-07-01',
            'data_fim' => '2026-07-07',
            'status' => 'pago',
        ]);

        $descontoAtual = Lancamento::create([
            'funcionario_id' => $funcionario->id,
            'periodo_pagamento_id' => $periodoAberto->id,
            'tipo' => 'desconto',
            'valor' => 25,
            'data' => '2026-08-03',
        ]);
        Lancamento::create([
            'funcionario_id' => $funcionario->id,
            'periodo_pagamento_id' => $periodoAntigo->id,
            'tipo' => 'desconto',
            'valor' => 99,
            'data' => '2026-07-03',
        ]);

        $this->actingAs($user)
            ->get(route('lancamentos.index'))
            ->assertOk()
            ->assertSee('Semana atual')
            ->assertSee('Desconto')
            ->assertSee('R$ 25,00')
            ->assertDontSee('R$ 99,00');
    }

    public function test_nao_permite_alterar_lancamento_de_periodo_fechado(): void
    {
        $user = User::factory()->create();
        $funcionario = Funcionario::create([
            'nome' => 'Pedro',
            'diaria_atual' => 180,
            'ativo' => true,
        ]);
        $periodo = PeriodoPagamento::create([
            'nome' => 'Quinzena 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-15',
            'status' => 'fechado',
            'fechado_em' => now(),
        ]);
        $lancamento = Lancamento::create([
            'funcionario_id' => $funcionario->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'adiantamento',
            'valor' => 40,
            'data' => '2026-08-02',
        ]);

        $this->actingAs($user)
            ->delete(route('lancamentos.destroy', $lancamento))
            ->assertRedirect();

        $this->assertDatabaseHas('lancamentos', ['id' => $lancamento->id]);
    }
}
