<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'nombre']);
        });

        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('imagen', 255);
            $table->integer('orden')->default(0);
            $table->string('aspect_ratio', 20)->default('1/1'); // 1/1, 2/3, 3/2, etc
            $table->timestamps();
        });

        Schema::create('gallery_image_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_image_id')->constrained('gallery_images')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->unique(['gallery_image_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_image_products');
        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('galleries');
    }
};
