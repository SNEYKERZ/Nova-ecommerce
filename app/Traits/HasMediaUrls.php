<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasMediaUrls
{
    /**
     * Generar URL pública para una imagen almacenada.
     * Soporta rutas en storage/, imagenes/ (carpeta public), o URLs absolutas.
     */
    public function mediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    /**
     * Almacenar una imagen subida en public/images/carrusel/
     */
    public function storeVisualImage($file): string
    {
        $directory = public_path('images/carrusel');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = uniqid('visual_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'images/carrusel/' . $filename;
    }

    /**
     * Eliminar un archivo de imagen (ya sea en public/ o en storage/)
     */
    public function deleteMediaPath(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (str_starts_with($path, 'images/')) {
            $absolutePath = public_path($path);
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
