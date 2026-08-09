<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->string('locomocao_tipo')->default('nenhuma')->after('diaria_atual');
            $table->decimal('locomocao_valor', 10, 2)->default(0)->after('locomocao_tipo');
        });

        Schema::table('presencas', function (Blueprint $table) {
            $table->string('locomocao_tipo')->default('nenhuma')->after('valor_aplicado');
            $table->decimal('valor_locomocao', 10, 2)->default(0)->after('locomocao_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropColumn(['locomocao_tipo', 'locomocao_valor']);
        });

        Schema::table('presencas', function (Blueprint $table) {
            $table->dropColumn(['locomocao_tipo', 'valor_locomocao']);
        });
    }
};
