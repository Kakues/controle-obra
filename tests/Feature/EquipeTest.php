<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use App\Models\Lancamento;
use App\Models\PeriodoPagamento;
use App\Models\User;
use App\Services\EquipeService;
use App\Services\PeriodoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_cadastrar_ajudante_vinculado_a_lider(): void
    {
        $user = User::factory()->create();
        $leo = Funcionario::create([
            'nome' => 'Leo',
            'regime' => 'empreita',
            'ativo' => true,
        ]);

        $this->actingAs($user)->post(route('funcionarios.store'), [
            'nome' => 'Carlos',
            'regime' => 'diaria',
            'superior_id' => $leo->id,
            'diaria_atual' => 120,
            'locomocao_tipo' => 'nenhuma',
        ])->assertRedirect(route('funcionarios.index'));

        $this->assertDatabaseHas('funcionarios', [
            'nome' => 'Carlos',
            'superior_id' => $leo->id,
        ]);
    }

    public function test_lider_com_equipe_nao_pode_ter_superior(): void
    {
        $user = User::factory()->create();
        $leo = Funcionario::create(['nome' => 'Leo', 'regime' => 'diaria', 'diaria_atual' => 200, 'ativo' => true]);
        Funcionario::create([
            'nome' => 'Carlos',
            'regime' => 'diaria',
            'superior_id' => $leo->id,
            'diaria_atual' => 120,
            'ativo' => true,
        ]);
        $maria = Funcionario::create(['nome' => 'Maria', 'regime' => 'diaria', 'diaria_atual' => 150, 'ativo' => true]);

        $this->actingAs($user)->put(route('funcionarios.update', $leo), [
            'nome' => 'Leo',
            'regime' => 'diaria',
            'superior_id' => $maria->id,
            'diaria_atual' => 200,
            'locomocao_tipo' => 'nenhuma',
        ])->assertSessionHasErrors('superior_id');
    }

    public function test_resumo_agrupa_equipe_com_total_de_referencia(): void
    {
        $leo = Funcionario::create(['nome' => 'Leo', 'regime' => 'empreita', 'ativo' => true]);
        $carlos = Funcionario::create([
            'nome' => 'Carlos',
            'regime' => 'diaria',
            'superior_id' => $leo->id,
            'diaria_atual' => 100,
            'ativo' => true,
        ]);
        $periodo = PeriodoPagamento::create([
            'nome' => 'Semana 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'aberto',
        ]);

        Lancamento::create([
            'funcionario_id' => $leo->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'empreita',
            'valor' => 800,
            'data' => '2026-08-07',
        ]);

        Lancamento::create([
            'funcionario_id' => $carlos->id,
            'periodo_pagamento_id' => $periodo->id,
            'tipo' => 'bonus',
            'valor' => 50,
            'data' => '2026-08-05',
        ]);

        $resumo = app(PeriodoService::class)->resumo($periodo);
        $agrupado = app(EquipeService::class)->blocosResumo($resumo);

        $this->assertCount(1, $agrupado['blocos']);
        $this->assertEquals(850.0, $agrupado['blocos']->first()['total_equipe']);
        $this->assertCount(0, $agrupado['solos']);
    }

    public function test_remover_lider_desvincula_equipe(): void
    {
        $user = User::factory()->create();
        $leo = Funcionario::create(['nome' => 'Leo', 'regime' => 'diaria', 'diaria_atual' => 200, 'ativo' => true]);
        $carlos = Funcionario::create([
            'nome' => 'Carlos',
            'regime' => 'diaria',
            'superior_id' => $leo->id,
            'diaria_atual' => 120,
            'ativo' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('funcionarios.destroy', $leo))
            ->assertRedirect();

        $this->assertNull($carlos->fresh()->superior_id);
    }
}
