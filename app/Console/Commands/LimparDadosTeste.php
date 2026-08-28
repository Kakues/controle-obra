<?php

namespace App\Console\Commands;

use App\Models\DiariaHistorico;
use App\Models\Funcionario;
use App\Models\Lancamento;
use App\Models\Obra;
use App\Models\PagamentoFuncionario;
use App\Models\PeriodoPagamento;
use App\Models\Presenca;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LimparDadosTeste extends Command
{
    protected $signature = 'app:limpar-dados-teste {--force : Não pedir confirmação}';

    protected $description = 'Apaga dados de obra/pessoal/presenças e recria usuários admin e ajudante';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Isso apaga TODOS os dados de uso (obras, pessoal, presenças, pagamentos). Continuar?')) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        PagamentoFuncionario::query()->delete();
        Lancamento::query()->delete();
        Presenca::query()->delete();
        DiariaHistorico::query()->delete();
        PeriodoPagamento::query()->delete();
        Funcionario::query()->delete();
        Obra::query()->delete();
        User::query()->delete();

        // limpa jobs/cache de sessão antigas se existirem
        foreach (['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $tabela) {
            if (Schema::hasTable($tabela)) {
                DB::table($tabela)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->call('db:seed', ['--class' => AdminSeeder::class, '--force' => true]);

        $this->info('Dados de teste removidos.');
        $this->line('Admin: admin@controleobra.test / password');
        $this->line('Ajudante: ajuda@controleobra.test / password');
        $this->warn('Troque as senhas no perfil assim que entrar.');

        return self::SUCCESS;
    }
}
