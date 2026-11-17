<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait HasMediaUrls
{
    /**
     * Generar URL pública para una imagen almacenada.
     * Soporta rutas en storage/, imagenes/ (carpeta public), o URLs absolutas.
     */
    public function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }

    /**
     * Almacenar una imagen subida en public/images/carrusel/
     */
    public function storeVisualImage($file): string
    {
        $directory = public_path('images/carrusel');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // La extensión se deduce del CONTENIDO del archivo (no del nombre que
        // envía el cliente) y se limita a imágenes: evita subir p. ej. un
        // "banner.html" con cabecera PNG que se serviría desde public/.
        $extension = strtolower((string) $file->guessExtension());
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw ValidationException::withMessages([
                'imagen' => 'La imagen debe ser JPG, PNG o WEBP.',
            ]);
        }

        $filename = uniqid('visual_', true).'.'.$extension;
        $file->move($directory, $filename);

        return 'images/carrusel/'.$filename;
    }

    /**
     * Eliminar un archivo de imagen (ya sea en public/ o en storage/)
     */
    public function deleteMediaPath(?string $path): void
    {
        if (! $path || str_contains($path, '..') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
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
