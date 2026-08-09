<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diaria_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->cascadeOnDelete();
            $table->decimal('valor', 10, 2);
            $table->date('vigente_desde');
            $table->timestamps();

            $table->unique(['funcionario_id', 'vigente_desde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diaria_historicos');
    }
};
