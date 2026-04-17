<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('destacado')->default(false)->after('estado');
            $table->boolean('nuevo')->default(false)->after('destacado');
            $table->text('descripcion')->nullable()->after('tallas');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['destacado', 'nuevo', 'descripcion']);
        });
    }
};