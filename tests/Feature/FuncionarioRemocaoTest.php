<?php

namespace Tests\Feature;

use App\Models\Funcionario;
use App\Models\Obra;
use App\Models\Presenca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuncionarioRemocaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_remover_da_equipe_preserva_historico(): void
    {
        $user = User::factory()->create();
        $obra = Obra::create(['nome' => 'Obra A', 'ativa' => true]);
        $funcionario = Funcionario::create([
            'nome' => 'João',
            'diaria_atual' => 150,
            'ativo' => true,
        ]);

        Presenca::create([
            'funcionario_id' => $funcionario->id,
            'obra_id' => $obra->id,
            'data' => '2026-08-01',
            'tipo' => 'integral',
            'valor_aplicado' => 150,
            'locomocao_tipo' => 'nenhuma',
            'valor_locomocao' => 0,
        ]);

        $this->actingAs($user)
            ->delete(route('funcionarios.destroy', $funcionario))
            ->assertRedirect();

        $funcionario->refresh();

        $this->assertFalse($funcionario->ativo);
        $this->assertDatabaseHas('funcionarios', ['id' => $funcionario->id]);
        $this->assertDatabaseHas('presencas', [
            'funcionario_id' => $funcionario->id,
            'data' => '2026-08-01',
        ]);
    }

    public function test_pode_reativar_funcionario(): void
    {
        $user = User::factory()->create();
        $funcionario = Funcionario::create([
            'nome' => 'Carlos',
            'diaria_atual' => 140,
            'ativo' => false,
        ]);

        $this->actingAs($user)
            ->post(route('funcionarios.reativar', $funcionario))
            ->assertRedirect(route('funcionarios.show', $funcionario));

        $this->assertTrue($funcionario->fresh()->ativo);
    }
}
