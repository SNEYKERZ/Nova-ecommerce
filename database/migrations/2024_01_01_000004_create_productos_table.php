<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('referencia')->unique();
            $table->decimal('precio', 10, 2);
            $table->string('foto')->nullable();
            $table->string('tallas')->nullable(); // Stored as comma-separated: S,M,L,XL
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->enum('estado', ['DISPONIBLE', 'NO_DISPONIBLE'])->default('DISPONIBLE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};