<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plantillas/themes para diferentes temporadas/festividades
        Schema::create('plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // ej: "Navidad 2024", "San Valentín", "Default"
            $table->string('slug')->unique(); // ej: navidad-2024, san-valentin
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->boolean('esta_activa')->default(false);
            $table->json('configuracion')->nullable(); // Colores, banner, CSS adicional, etc.
            $table->timestamps();
        });

        // Banners promocionales
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('imagen'); // Banner principal
            $table->string('imagen_mobile')->nullable(); // Banner para móvil
            $table->string('url_destino')->nullable(); // Link al que dirige
            $table->foreignId('plantilla_id')->nullable()->constrained('plantillas')->onDelete('set null');
            $table->integer('orden')->default(0);
            $table->boolean('esta_activo')->default(true);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });

        // Slides del carrusel
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen');
            $table->string('imagen_mobile')->nullable();
            $table->string('boton_texto')->nullable();
            $table->string('boton_url')->nullable();
            $table->string('estilo')->default('dark'); // dark, light, transparent
            $table->integer('orden')->default(0);
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slides');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('plantillas');
    }
};