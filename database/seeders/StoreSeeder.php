<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        // Tienda demo para desarrollo
        Store::updateOrCreate(
            ['slug' => 'demo'],
            [
                'nombre' => 'Mi Tienda Demo',
                'dominio' => null,
                'email' => 'admin@mitienda.com',
                'telefono' => '+51 999 999 999',
                'descripcion' => 'Tienda demo de Vendex',
                'configuracion' => [
                    'color_primary' => '#0c6e09',
                    'color_secondary' => '#343a40',
                ],
                'activo' => true,
            ]
        );
    }
}