<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['categoria' => 'CAMISETA', 'tipoDePrenda' => 'CAMISETA'],
            ['categoria' => 'BASICA', 'tipoDePrenda' => 'BASICA'],
            ['categoria' => 'SUMA', 'tipoDePrenda' => 'SUMA'],
            ['categoria' => 'OTRAS', 'tipoDePrenda' => 'OTRAS'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}