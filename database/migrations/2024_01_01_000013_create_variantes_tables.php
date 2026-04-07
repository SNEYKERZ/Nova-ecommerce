<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Variantes de producto (talla/color con stock individual)
        Schema::create('producto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->string('sku')->unique()->nullable(); // SKU único de la variante
            $table->string('talla')->nullable();
            $table->string('color')->nullable();
            $table->integer('stock')->default(0); // Stock individual
            $table->decimal('precio_adicional', 10, 2)->default(0); // Precio extra por variante
            $table->boolean('esta_activa')->default(true);
            $table->timestamps();

            $table->unique(['producto_id', 'talla', 'color']);
        });

        // Tabla de colores (para el selector)
        Schema::create('colores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // ej: "Rojo", "Azul Marino"
            $table->string('codigo_hex')->nullable(); // ej: "#FF0000"
            $table->timestamps();
        });

        // Tallas predefinidas
        Schema::create('tallas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // ej: "S", "M", "L", "XL"
            $table->string('tipo')->default('ropa'); // ropa, calzado, accesorios
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallas');
        Schema::dropIfExists('colores');
        Schema::dropIfExists('producto_variantes');
    }
};