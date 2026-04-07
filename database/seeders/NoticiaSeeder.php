<?php

namespace Database\Seeders;

use App\Models\Noticia;
use Illuminate\Database\Seeder;

class NoticiaSeeder extends Seeder
{
    public function run(): void
    {
        Noticia::updateOrCreate(
            ['id' => 1],
            [
                'campos_adicionales' => 'Nueva coleccion urbana 2026, 20% off en segunda prenda, Envio gratis desde $180.000'
            ]
        );
    }
}
