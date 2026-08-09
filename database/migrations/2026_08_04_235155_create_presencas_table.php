<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presencas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->cascadeOnDelete();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->date('data');
            $table->string('tipo')->default('integral'); // integral, meio, especial
            $table->decimal('valor_aplicado', 10, 2);
            $table->text('observacao')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['funcionario_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presencas');
    }
};
