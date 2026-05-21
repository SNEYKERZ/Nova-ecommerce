<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command->warn('No hay tiendas. Ejecuta el StoreSeeder primero.');
            return;
        }

        Categoria::ignoreTenantScope();

        $categorias = [
            ['categoria' => 'CAMISETAS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'PANTALONES', 'tipoDePrenda' => 'INFERIOR'],
            ['categoria' => 'CHAQUETAS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'BUZOS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'ACCESORIOS', 'tipoDePrenda' => 'ACCESORIO'],
        ];

        foreach ($stores as $store) {
            foreach ($categorias as $categoria) {
                Categoria::updateOrCreate(
                    ['categoria' => $categoria['categoria'], 'store_id' => $store->id],
                    array_merge($categoria, ['store_id' => $store->id])
                );
            }

            $this->command->line("Categorías creadas para: {$store->slug}");
        }

        Categoria::restoreTenantScope();
    }
}
