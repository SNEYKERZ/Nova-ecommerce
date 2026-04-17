<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar el índice único actual en referencia
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_referencia_unique');
        });

        // Agregar índice único compuesto (referencia + store_id)
        Schema::table('productos', function (Blueprint $table) {
            $table->unique(['referencia', 'store_id'], 'productos_referencia_store_unique');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_referencia_store_unique');
        });
        $table->unique('referencia', 'productos_referencia_unique');
    }
};