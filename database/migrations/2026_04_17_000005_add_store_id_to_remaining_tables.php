<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar store_id a carritos
        Schema::table('carritos', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a carrito_items
        Schema::table('carrito_items', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a pedido_items
        Schema::table('pedido_items', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a wishlists
        Schema::table('wishlists', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a historial_precios
        Schema::table('historial_precios', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });
    }

    public function down(): void
    {
        $tables = ['carritos', 'carrito_items', 'pedido_items', 'wishlists', 'historial_precios'];
        
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $t) {
                $t->dropForeign(['store_id']);
                $t->dropColumn('store_id');
            });
        }
    }
};