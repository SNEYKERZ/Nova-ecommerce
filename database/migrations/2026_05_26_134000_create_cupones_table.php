<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('codigo', 50);
            $table->enum('tipo', ['porcentaje', 'fijo']);
            $table->decimal('valor_descuento', 10, 2);
            $table->decimal('monto_minimo', 10, 2)->nullable();
            $table->unsignedInteger('max_usos')->nullable();
            $table->unsignedInteger('usos_actuales')->default(0);
            $table->dateTime('fecha_expiracion')->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupones');
    }
};
