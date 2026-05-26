<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carritos', function (Blueprint $table) {
            $table->foreignId('cupon_id')->nullable()->constrained('cupones')->nullOnDelete()->after('estado');
            $table->decimal('descuento_cupon', 10, 2)->nullable()->after('cupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('carritos', function (Blueprint $table) {
            $table->dropForeign(['cupon_id']);
            $table->dropColumn(['cupon_id', 'descuento_cupon']);
        });
    }
};
