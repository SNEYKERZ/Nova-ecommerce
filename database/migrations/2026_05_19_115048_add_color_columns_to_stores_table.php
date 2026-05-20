<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('bg_color', 20)->nullable()->default('#ffffff')->after('activo');
            $table->string('navbar_color', 20)->nullable()->default('#1e293b')->after('bg_color');
            $table->string('footer_color', 20)->nullable()->default('#1e293b')->after('navbar_color');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['bg_color', 'navbar_color', 'footer_color']);
        });
    }
};
