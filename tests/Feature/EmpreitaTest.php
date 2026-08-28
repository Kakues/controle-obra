<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use App\Models\Lancamento;
use App\Models\Obra;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use App\Models\User;
use App\Services\PeriodoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpreitaTest extends TestCase
{
    use RefreshDatabase;

    public function test_empreiteiro_nao_aparece_na_marcacao_de_presenca(): void
    {
        $user = User::factory()->create();
        Funcionario::create([
            'nome' => 'João Diária',
            'regime' => 'diaria',
            'diaria_atual' => 150,
            'ativo' => true,
        ]);
        Funcionario::create([
            'nome' => 'Pedro Empreita',
            'regime' => 'empreita',
            'diaria_atual' => 0,
            'ativo' => true,
        ]);

        $this->actingAs($user)
            ->get(route('presencas.marcacao', ['data' => '2026-08-10']))
            ->assertOk()
            ->assertSee('João Diária')
            ->assertDontSee('Pedro Empreita');
    }

    public function test_valor_empreita_entra_no_resumo_do_periodo(): void
    {
        $user = User::factory()->create();
        $empreiteiro = Funcionario::create([
            'nome' => 'Pedro Empreita',
            'regime' => 'empreita',
            'diaria_atual' => 0,
            'ativo' => true,
        ]);
        $periodo = PeriodoPagamento::create([
            'nome' => 'Semana 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'aberto',
        ]);

        $this->actingAs($user)->post(route('lancamentos.store'), [
            'funcionario_id' => $empreiteiro->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'empreita',
            'valor' => 1200,
            'data' => '2026-08-07',
            'descricao' => 'Semana fechamento',
        ])->assertRedirect(route('lancamentos.index'));

        $this->assertDatabaseHas('lancamentos', [
            'funcionario_id' => $empreiteiro->id,
            'tipo' => 'empreita',
            'valor' => 1200,
        ]);

        $resumo = app(PeriodoService::class)->resumo($periodo);
        $item = $resumo->first();

        $this->assertEquals(1200.0, $item['empreitas']);
        $this->assertEquals(1200.0, $item['a_pagar']);
        $this->assertSame('Pedro Empreita', $item['funcionario']->nome);
    }

    public function test_empreita_com_adiantamento_calcula_a_pagar(): void
    {
        $empreiteiro = Funcionario::create([
            'nome' => 'Carlos',
            'regime' => 'empreita',
            'diaria_atual' => 0,
            'ativo' => true,
        ]);
        $periodo = PeriodoPagamento::create([
            'nome' => 'Semana 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'aberto',
        ]);

        Lancamento::create([
            'funcionario_id' => $empreiteiro->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'empreita',
            'valor' => 1000,
            'data' => '2026-08-07',
        ]);
        Lancamento::create([
            'funcionario_id' => $empreiteiro->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'adiantamento',
            'valor' => 200,
            'data' => '2026-08-03',
        ]);

        $item = app(PeriodoService::class)->resumo($periodo)->first();

        $this->assertEquals(1000.0, $item['empreitas']);
        $this->assertEquals(200.0, $item['adiantamentos']);
        $this->assertEquals(800.0, $item['a_pagar']);
    }

    public function test_nao_grava_presenca_para_empreiteiro_mesmo_enviado_no_post(): void
    {
        $user = User::factory()->create();
        $obra = Obra::create(['nome' => 'Casa 1', 'ativa' => true]);
        $empreiteiro = Funcionario::create([
            'nome' => 'Pedro',
            'regime' => 'empreita',
            'diaria_atual' => 0,
            'ativo' => true,
        ]);

        $this->actingAs($user)->post(route('presencas.marcacao.store'), [
            'data' => '2026-08-10',
            'obra_padrao_id' => $obra->id,
            'marcacoes' => [
                $empreiteiro->id => [
                    'presente' => '1',
                    'obra_id' => $obra->id,
                    'tipo' => 'integral',
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(0, Presenca::query()->count());
    }
}
