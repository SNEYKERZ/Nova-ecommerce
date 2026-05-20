<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->unsignedTinyInteger('posicion'); // 1 izquierda, 2 derecha
            $table->string('imagen');
            $table->string('url_destino')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'posicion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_banners');
    }
};
