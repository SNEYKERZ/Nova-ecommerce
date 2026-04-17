<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar store_id a categorias (nullable por si hay datos existentes)
        Schema::table('categorias', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a productos
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a clientes
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a pedidos
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a ofertas
        Schema::table('ofertas', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a banners
        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a slides
        Schema::table('slides', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a noticias
        Schema::table('noticias', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a producto_imagenes
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a producto_variantes
        Schema::table('producto_variantes', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a resenas
        Schema::table('resenas', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a insumos
        Schema::table('insumos', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a usuarios (para admins de cada store)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });

        // Agregar store_id a plantillas
        Schema::table('plantillas', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade')->after('id');
        });
    }

    public function down(): void
    {
        $tables = ['categorias', 'productos', 'clientes', 'pedidos', 'ofertas', 'banners', 'slides', 'noticias', 'producto_imagenes', 'producto_variantes', 'resenas', 'insumos', 'users', 'plantillas'];
        
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $t) {
                $t->dropForeign(['store_id']);
                $t->dropColumn('store_id');
            });
        }
    }
};