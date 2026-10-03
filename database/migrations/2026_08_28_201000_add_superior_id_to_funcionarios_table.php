<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->foreignId('superior_id')
                ->nullable()
                ->after('regime')
                ->constrained('funcionarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('superior_id');
        });
    }
};
