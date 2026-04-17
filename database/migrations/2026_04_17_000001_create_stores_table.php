<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // tienda1, tienda2, etc.
            $table->string('nombre'); // Nombre de la tienda
            $table->string('dominio')->nullable()->unique(); // mitienda.com (opcional)
            $table->string('logo_path')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->text('descripcion')->nullable();
            $table->json('configuracion')->nullable(); // Colores, banner, etc.
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};