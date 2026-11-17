<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // Drop FK, make nullable, re-add with SET NULL
            DB::statement('ALTER TABLE pedidos DROP FOREIGN KEY pedidos_cliente_id_foreign');
            DB::statement('ALTER TABLE pedidos MODIFY cliente_id BIGINT(20) UNSIGNED NULL');
            DB::statement('ALTER TABLE pedidos ADD CONSTRAINT pedidos_cliente_id_foreign FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL');

            return;
        }

        // Otros motores (SQLite en tests): equivalente portable
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
            $table->foreign('cliente_id')->references('id')->on('clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE pedidos DROP FOREIGN KEY pedidos_cliente_id_foreign');
            DB::statement('ALTER TABLE pedidos MODIFY cliente_id BIGINT(20) UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE pedidos ADD CONSTRAINT pedidos_cliente_id_foreign FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE');

            return;
        }

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
        });
    }
};
