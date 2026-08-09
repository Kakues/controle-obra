<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamento_funcionarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_pagamento_id')->constrained('periodo_pagamentos')->cascadeOnDelete();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->cascadeOnDelete();
            $table->string('forma');
            $table->decimal('valor', 10, 2);
            $table->date('data_pagamento')->nullable();
            $table->string('observacao')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periodo_pagamento_id', 'funcionario_id'], 'pag_func_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_funcionarios');
    }
};
