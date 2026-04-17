<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el store demo
        $store = Store::where('slug', 'demo')->first();
        
        if (!$store) {
            $this->command->warn('No se encontró el store demo. Ejecuta el StoreSeeder primero.');
            return;
        }

        // Ignorar el scope de tenant para el seeder
        Categoria::ignoreTenantScope();

        $categorias = [
            ['categoria' => 'CAMISETAS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'PANTALONES', 'tipoDePrenda' => 'INFERIOR'],
            ['categoria' => 'CHAQUETAS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'BUZOS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'ACCESORIOS', 'tipoDePrenda' => 'ACCESORIO'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(
                ['categoria' => $categoria['categoria'], 'store_id' => $store->id],
                array_merge($categoria, ['store_id' => $store->id])
            );
        }

        Categoria::restoreTenantScope();
    }
}
