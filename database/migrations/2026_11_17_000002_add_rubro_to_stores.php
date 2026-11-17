<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo de negocio de la tienda: ajusta la estética base del storefront
     * (tipografía, densidad, vocabulario). Valores: general, moda, restaurante, autopartes.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('rubro', 20)->default('general')->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('rubro');
        });
    }
};
