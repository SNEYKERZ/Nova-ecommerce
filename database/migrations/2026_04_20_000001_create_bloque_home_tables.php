<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de bloques home
        Schema::create('bloque_homes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->enum('tipo', ['banner', 'texto'])->default('banner');
            $table->tinyInteger('posicion')->unsigned(); // 1 o 2
            $table->string('titulo')->nullable();
            $table->text('contenido')->nullable();
            $table->enum('tamano_texto', ['normal', 'grande'])->default('normal');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['store_id', 'posicion']);
        });

        // Tabla de imágenes para bloques banner
        Schema::create('bloque_home_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloque_home_id')->constrained('bloque_homes')->onDelete('cascade');
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->string('imagen');
            $table->string('url_destino')->nullable();
            $table->tinyInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloque_home_imagenes');
        Schema::dropIfExists('bloque_homes');
    }
};