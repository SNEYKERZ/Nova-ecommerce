<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('navbar_text_color', 20)->nullable()->default('#ffffff')->after('navbar_color');
            $table->string('footer_text_color', 20)->nullable()->default('#ffffff')->after('footer_color');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['navbar_text_color', 'footer_text_color']);
        });
    }
};
