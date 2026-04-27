<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Presupuesto inicial que tendrá cada equipo al unirse (en céntimos de €)
            // Por defecto 50M, el creador de la liga puede configurarlo
            $table->integer('presupuesto_inicial')->default(50_000_000)->after('season');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('presupuesto_inicial');
        });
    }
};
