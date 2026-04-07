<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wishlist/Favoritos de usuarios
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('sesion_id')->nullable(); // Para usuarios no autenticados
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'producto_id']);
            $table->unique(['sesion_id', 'producto_id']);
        });

        // Reseñas y valoraciones de productos
        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nombre')->nullable(); // Nombre del que reseña (si no está logueado)
            $table->string('email')->nullable(); // Email del que reseña
            $table->tinyInteger('calificacion'); // 1-5 estrellas
            $table->text('comentario')->nullable();
            $table->boolean('aprobada')->default(false); // Moderación
            $table->timestamps();
        });

        // Historial de precios (para ofertas y seguimiento)
        Schema::create('historial_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->decimal('precio_anterior', 10, 2);
            $table->decimal('precio_nuevo', 10, 2);
            $table->string('motivo')->nullable(); // 'oferta', 'ajuste', 'incremento'
            $table->timestamps();
        });

        // Ofertasflash promociones
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->onDelete('cascade');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->decimal('descuento_porcentaje', 5, 2)->nullable();
            $table->decimal('descuento_fijo', 10, 2)->nullable();
            $table->decimal('precio_oferta', 10, 2)->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->boolean('esta_activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas');
        Schema::dropIfExists('historial_precios');
        Schema::dropIfExists('resenas');
        Schema::dropIfExists('wishlists');
    }
};