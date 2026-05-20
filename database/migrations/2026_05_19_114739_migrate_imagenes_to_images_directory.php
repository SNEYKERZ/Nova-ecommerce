<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mover archivos del directorio imagenes/carrusel/ a images/carrusel/
        $oldDir = public_path('imagenes/carrusel');
        $newDir = public_path('images/carrusel');

        if (is_dir($oldDir)) {
            if (!is_dir($newDir)) {
                mkdir($newDir, 0755, true);
            }

            $files = scandir($oldDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $oldPath = $oldDir . '/' . $file;
                $newPath = $newDir . '/' . $file;

                if (is_file($oldPath) && !file_exists($newPath)) {
                    rename($oldPath, $newPath);
                }
            }

            // Eliminar directorio viejo si quedó vacío
            if (count(scandir($oldDir)) <= 2) {
                rmdir($oldDir);
                // También eliminar imagenes/ si quedó vacío
                $parentOld = public_path('imagenes');
                if (is_dir($parentOld) && count(scandir($parentOld)) <= 2) {
                    rmdir($parentOld);
                }
            }
        }

        // Actualizar paths en catalog_banners
        DB::table('catalog_banners')
            ->where('imagen', 'like', 'imagenes/%')
            ->update([
                'imagen' => DB::raw("REPLACE(imagen, 'imagenes/', 'images/')")
            ]);

        // Actualizar paths en bloque_home_imagenes
        DB::table('bloque_home_imagenes')
            ->where('imagen', 'like', 'imagenes/%')
            ->update([
                'imagen' => DB::raw("REPLACE(imagen, 'imagenes/', 'images/')")
            ]);
    }

    public function down(): void
    {
        // Revertir paths en DB
        DB::table('catalog_banners')
            ->where('imagen', 'like', 'images/%')
            ->update([
                'imagen' => DB::raw("REPLACE(imagen, 'images/', 'imagenes/')")
            ]);

        DB::table('bloque_home_imagenes')
            ->where('imagen', 'like', 'images/%')
            ->update([
                'imagen' => DB::raw("REPLACE(imagen, 'images/', 'imagenes/')")
            ]);

        // Mover archivos de vuelta
        $oldDir = public_path('imagenes/carrusel');
        $newDir = public_path('images/carrusel');

        if (is_dir($newDir)) {
            if (!is_dir($oldDir)) {
                mkdir($oldDir, 0755, true);
            }

            $files = scandir($newDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $newPath = $newDir . '/' . $file;
                $oldPath = $oldDir . '/' . $file;

                if (is_file($newPath) && !file_exists($oldPath)) {
                    rename($newPath, $oldPath);
                }
            }
        }
    }
};
