<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Endurecimiento multi-tenant y control de inventario de pedidos.
 *
 * - pedidos.stock_descontado: indica si el stock de las variantes ya fue
 *   descontado para el pedido (evita doble descuento / permite reponer al cancelar).
 * - Unicidades globales que rompían el multi-tenant pasan a ser por tienda:
 *   clientes.email, insumos.sku y producto_variantes.sku.
 *
 * No elimina datos: sólo agrega una columna y cambia índices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->boolean('stock_descontado')->default(false)->after('estado');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_email_unique');
            $table->unique(['store_id', 'email'], 'clientes_store_email_unique');
        });

        Schema::table('insumos', function (Blueprint $table) {
            $table->dropUnique('insumos_sku_unique');
            $table->unique(['store_id', 'sku'], 'insumos_store_sku_unique');
        });

        Schema::table('producto_variantes', function (Blueprint $table) {
            $table->dropUnique('producto_variantes_sku_unique');
            $table->unique(['store_id', 'sku'], 'producto_variantes_store_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('producto_variantes', function (Blueprint $table) {
            $table->dropUnique('producto_variantes_store_sku_unique');
            $table->unique('sku', 'producto_variantes_sku_unique');
        });

        Schema::table('insumos', function (Blueprint $table) {
            $table->dropUnique('insumos_store_sku_unique');
            $table->unique('sku', 'insumos_sku_unique');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_store_email_unique');
            $table->unique('email', 'clientes_email_unique');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('stock_descontado');
        });
    }
};
