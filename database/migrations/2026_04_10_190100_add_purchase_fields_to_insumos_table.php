<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            $table->string('tipo_registro', 20)->default('UNIDAD')->after('unidad');
            $table->integer('unidades_por_paquete')->nullable()->after('tipo_registro');
            $table->integer('cantidad_compra')->nullable()->after('unidades_por_paquete');
            $table->decimal('costo_total_compra', 10, 2)->nullable()->after('cantidad_compra');
        });
    }

    public function down(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_registro',
                'unidades_por_paquete',
                'cantidad_compra',
                'costo_total_compra',
            ]);
        });
    }
};
