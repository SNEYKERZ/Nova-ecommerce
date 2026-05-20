<?php

namespace Database\Seeders;

use App\Models\BloqueHome;
use App\Models\BloqueHomeImagen;
use Illuminate\Database\Seeder;

class CarouselImagesSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar que existe el bloque home posición 1
        $bloque = BloqueHome::withoutGlobalScopes()->firstOrCreate(
            ['posicion' => 1],
            [
                'tipo' => 'banner',
                'activo' => true,
            ]
        );

        // Imágenes hardcodeadas que existían en index.blade.php
        $imagenes = [
            ['file' => 'images/carrusel/6.jpg', 'nombre' => 'Promo 1', 'orden' => 1],
            ['file' => 'images/carrusel/4.jpg', 'nombre' => 'Promo 2', 'orden' => 2],
            ['file' => 'images/carrusel/1.jpg', 'nombre' => 'Promo 3', 'orden' => 3],
        ];

        foreach ($imagenes as $img) {
            // Solo crear si el archivo existe y no hay ya un registro con esa imagen
            if (!file_exists(public_path($img['file']))) {
                $this->command->warn("Archivo no encontrado: {$img['file']}");
                continue;
            }

            $exists = BloqueHomeImagen::withoutGlobalScopes()
                ->where('bloque_home_id', $bloque->id)
                ->where('imagen', $img['file'])
                ->exists();

            if (!$exists) {
                BloqueHomeImagen::withoutGlobalScopes()->create([
                    'bloque_home_id' => $bloque->id,
                    'imagen' => $img['file'],
                    'nombre' => $img['nombre'],
                    'identificador' => 'carrusel',
                    'url_destino' => null,
                    'orden' => $img['orden'],
                ]);
                $this->command->info("Creado: {$img['nombre']} ({$img['file']})");
            } else {
                $this->command->line("Ya existe: {$img['file']}");
            }
        }

        $this->command->info('✅ Imágenes del carrusel migradas correctamente.');
    }
}
