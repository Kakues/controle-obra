<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use App\Models\Obra;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use App\Models\User;
use App\Services\ObraCustoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminERelatoriosTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajudante_nao_fecha_periodo(): void
    {
        $ajudante = User::factory()->ajudante()->create();
        $periodo = PeriodoPagamento::create([
            'nome' => 'Semana',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'aberto',
        ]);

        $this->actingAs($ajudante)
            ->post(route('periodos.fechar', $periodo))
            ->assertForbidden();
    }

    public function test_admin_acessa_comprovante(): void
    {
        $admin = User::factory()->create();
        $periodo = PeriodoPagamento::create([
            'nome' => 'Semana',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-07',
            'status' => 'aberto',
        ]);

        $this->actingAs($admin)
            ->get(route('periodos.comprovante', $periodo))
            ->assertOk()
            ->assertSee('Comprovante de pagamento');
    }

    public function test_custo_por_obra_soma_diarias_e_locomocao(): void
    {
        $obra = Obra::create(['nome' => 'Casa Azul', 'ativa' => true]);
        $funcionario = Funcionario::create([
            'nome' => 'João',
            'diaria_atual' => 150,
            'ativo' => true,
        ]);

        Presenca::create([
            'funcionario_id' => $funcionario->id,
            'obra_id' => $obra->id,
            'data' => '2026-08-03',
            'tipo' => 'integral',
            'valor_aplicado' => 150,
            'locomocao_tipo' => 'onibus',
            'valor_locomocao' => 12,
        ]);

        $resumo = app(ObraCustoService::class)->resumir('2026-08-01', '2026-08-31');

        $this->assertCount(1, $resumo);
        $this->assertSame('Casa Azul', $resumo->first()['obra_nome']);
        $this->assertEquals(162.0, $resumo->first()['total']);
    }
}
