<?php

namespace Database\Seeders;

use App\Models\Noticia;
use Illuminate\Database\Seeder;

class NoticiaSeeder extends Seeder
{
    public function run(): void
    {
        Noticia::create([
            'campos_adicionales' => '2x1 en camisetas básicas, Descuento 15% en compras superiores a $200.000, Envío gratis en pedidos mayores a $150.000'
        ]);
    }
}