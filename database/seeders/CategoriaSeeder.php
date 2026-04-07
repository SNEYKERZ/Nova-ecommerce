<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['categoria' => 'CAMISETAS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'PANTALONES', 'tipoDePrenda' => 'INFERIOR'],
            ['categoria' => 'CHAQUETAS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'BUZOS', 'tipoDePrenda' => 'SUPERIOR'],
            ['categoria' => 'ACCESORIOS', 'tipoDePrenda' => 'ACCESORIO'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(
                ['categoria' => $categoria['categoria']],
                $categoria
            );
        }
    }
}
