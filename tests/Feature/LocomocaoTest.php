<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use App\Models\Obra;
use App\Models\Presenca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocomocaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_presenca_inclui_locomocao_do_funcionario(): void
    {
        $user = User::factory()->create();
        $obra = Obra::create(['nome' => 'Obra A', 'ativa' => true]);
        $funcionario = Funcionario::create([
            'nome' => 'João',
            'diaria_atual' => 150,
            'locomocao_tipo' => 'onibus',
            'locomocao_valor' => 12.5,
            'ativo' => true,
        ]);

        $this->actingAs($user)->post(route('presencas.marcacao.store'), [
            'data' => '2026-08-05',
            'obra_padrao_id' => $obra->id,
            'marcacoes' => [
                $funcionario->id => [
                    'presente' => '1',
                    'tipo' => 'integral',
                    'pagar_locomocao' => '1',
                ],
            ],
        ])->assertRedirect();

        $presenca = Presenca::query()->first();

        $this->assertNotNull($presenca);
        $this->assertSame('onibus', $presenca->locomocao_tipo);
        $this->assertEquals(12.5, (float) $presenca->valor_locomocao);
        $this->assertEquals(150.0, (float) $presenca->valor_aplicado);
    }

    public function test_pode_marcar_presenca_sem_locomocao_no_dia(): void
    {
        $user = User::factory()->create();
        $obra = Obra::create(['nome' => 'Obra A', 'ativa' => true]);
        $funcionario = Funcionario::create([
            'nome' => 'Carlos',
            'diaria_atual' => 140,
            'locomocao_tipo' => 'gasolina',
            'locomocao_valor' => 30,
            'ativo' => true,
        ]);

        $this->actingAs($user)->post(route('presencas.marcacao.store'), [
            'data' => '2026-08-05',
            'obra_padrao_id' => $obra->id,
            'marcacoes' => [
                $funcionario->id => [
                    'presente' => '1',
                    'tipo' => 'integral',
                    'pagar_locomocao' => '0',
                ],
            ],
        ])->assertRedirect();

        $presenca = Presenca::query()->first();

        $this->assertSame('nenhuma', $presenca->locomocao_tipo);
        $this->assertEquals(0.0, (float) $presenca->valor_locomocao);
    }
}
