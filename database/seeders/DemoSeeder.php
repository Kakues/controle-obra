<?php

namespace Database\Seeders;

use App\Models\DiariaHistorico;
use App\Models\Funcionario;
use App\Models\Obra;
use App\Models\User;
use App\Services\DiariaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@controleobra.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'ajuda@controleobra.test'],
            [
                'name' => 'Ajudante',
                'password' => Hash::make('password'),
                'role' => User::ROLE_AJUDANTE,
                'email_verified_at' => now(),
            ]
        );

        $obra = Obra::query()->updateOrCreate(
            ['nome' => 'Obra Exemplo'],
            [
                'endereco' => 'Rua das Obras, 100',
                'ativa' => true,
            ]
        );

        $diarias = app(DiariaService::class);

        $pessoas = [
            ['nome' => 'João Pedreiro', 'diaria_atual' => 180],
            ['nome' => 'Carlos Servente', 'diaria_atual' => 140],
            ['nome' => 'Pedro Armador', 'diaria_atual' => 200],
        ];

        foreach ($pessoas as $pessoa) {
            $funcionario = Funcionario::query()->updateOrCreate(
                ['nome' => $pessoa['nome']],
                [
                    'diaria_atual' => $pessoa['diaria_atual'],
                    'ativo' => true,
                ]
            );

            if (! DiariaHistorico::query()->where('funcionario_id', $funcionario->id)->exists()) {
                $diarias->atualizarDiaria($funcionario, (float) $pessoa['diaria_atual'], now()->startOfMonth());
            }
        }

        unset($obra);
    }
}
