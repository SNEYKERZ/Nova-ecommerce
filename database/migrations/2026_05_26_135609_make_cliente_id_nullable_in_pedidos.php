<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK, make nullable, re-add with SET NULL
        DB::statement('ALTER TABLE pedidos DROP FOREIGN KEY pedidos_cliente_id_foreign');
        DB::statement('ALTER TABLE pedidos MODIFY cliente_id BIGINT(20) UNSIGNED NULL');
        DB::statement('ALTER TABLE pedidos ADD CONSTRAINT pedidos_cliente_id_foreign FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pedidos DROP FOREIGN KEY pedidos_cliente_id_foreign');
        DB::statement('ALTER TABLE pedidos MODIFY cliente_id BIGINT(20) UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE pedidos ADD CONSTRAINT pedidos_cliente_id_foreign FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE');
    }
};
