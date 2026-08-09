<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use App\Models\Obra;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use App\Models\User;
use App\Services\PeriodoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresencaEntraNoPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_presenca_marcada_antes_do_periodo_aparece_no_resumo(): void
    {
        $obra = Obra::create(['nome' => 'Obra A', 'ativa' => true]);
        $funcionario = Funcionario::create([
            'nome' => 'João',
            'diaria_atual' => 150,
            'ativo' => true,
        ]);

        Presenca::create([
            'funcionario_id' => $funcionario->id,
            'obra_id' => $obra->id,
            'data' => '2026-08-04',
            'tipo' => 'integral',
            'valor_aplicado' => 150,
            'locomocao_tipo' => 'nenhuma',
            'valor_locomocao' => 0,
        ]);

        $periodo = PeriodoPagamento::create([
            'nome' => 'Semana 1',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'aberto',
        ]);

        $resumo = app(PeriodoService::class)->resumo($periodo);

        $this->assertCount(1, $resumo);
        $this->assertSame(1, $resumo->first()['dias']);
        $this->assertEquals(150.0, $resumo->first()['a_pagar']);
    }

    public function test_criar_periodo_avisa_presencas_ja_existentes(): void
    {
        $user = User::factory()->create();
        $obra = Obra::create(['nome' => 'Obra A', 'ativa' => true]);
        $funcionario = Funcionario::create([
            'nome' => 'Carlos',
            'diaria_atual' => 140,
            'ativo' => true,
        ]);

        Presenca::create([
            'funcionario_id' => $funcionario->id,
            'obra_id' => $obra->id,
            'data' => '2026-08-05',
            'tipo' => 'integral',
            'valor_aplicado' => 140,
            'locomocao_tipo' => 'nenhuma',
            'valor_locomocao' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('periodos.store'), [
            'nome' => 'Semana 2',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
        ]);

        $periodo = PeriodoPagamento::query()->first();

        $response->assertRedirect(route('periodos.show', $periodo));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('1 presença', session('success'));
    }
}
