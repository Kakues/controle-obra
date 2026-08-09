<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodo_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('status')->default('aberto'); // aberto, fechado, pago
            $table->timestamp('fechado_em')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodo_pagamentos');
    }
};
