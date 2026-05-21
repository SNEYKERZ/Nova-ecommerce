<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\GalleryImageProduct;
use App\Models\Producto;
use App\Traits\HasMediaUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    use HasMediaUrls;

    /**
     * Obtener todas las galerías de la tienda actual para admin
     */
    public function index()
    {
        $galleries = Gallery::with(['imagenes.productos.producto'])
            ->orderBy('orden')
            ->get()
            ->map(function (Gallery $gallery) {
                return [
                    'id' => $gallery->id,
                    'nombre' => $gallery->nombre,
                    'descripcion' => $gallery->descripcion,
                    'orden' => $gallery->orden,
                    'activo' => $gallery->activo,
                    'imagenes' => $gallery->imagenes->map(function (GalleryImage $img) {
                        return [
                            'id' => $img->id,
                            'imagen_url' => $this->mediaUrl($img->imagen),
                            'orden' => $img->orden,
                            'aspect_ratio' => $img->aspect_ratio,
                            'productos' => $img->productos->map(fn($gip) => [
                                'id' => $gip->id,
                                'producto_id' => $gip->producto_id,
                                'referencia' => $gip->producto->referencia,
                                'nombre' => $gip->producto->nombre,
                                'precio' => (float) $gip->producto->precio,
                            ])->values(),
                        ];
                    })->values(),
                ];
            });

        $productos = Producto::select('id', 'nombre', 'referencia', 'precio')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('admin/GalleryPage', [
            'galleries' => $galleries,
            'productos' => $productos,
        ]);
    }

    /**
     * Obtener detalle de una galería con imágenes y productos
     */
    public function show(Gallery $gallery): JsonResponse
    {
        $imagenes = $gallery->imagenes()
            ->with(['productos.producto:id,referencia,nombre,precio,foto'])
            ->orderBy('orden')
            ->get()
            ->map(function (GalleryImage $img) {
                return [
                    'id' => $img->id,
                    'imagen' => $this->mediaUrl($img->imagen),
                    'orden' => $img->orden,
                    'aspect_ratio' => $img->aspect_ratio,
                    'productos' => $img->productos->map(fn($gip) => [
                        'id' => $gip->id,
                        'producto_id' => $gip->producto_id,
                        'referencia' => $gip->producto->referencia,
                        'nombre' => $gip->producto->nombre,
                        'precio' => (float) $gip->producto->precio,
                        'foto' => $this->mediaUrl($gip->producto->foto),
                        'orden' => $gip->orden,
                    ])->values(),
                ];
            });

        return response()->json([
            'galeria' => [
                'id' => $gallery->id,
                'nombre' => $gallery->nombre,
                'descripcion' => $gallery->descripcion,
                'orden' => $gallery->orden,
                'activo' => $gallery->activo,
            ],
            'imagenes' => $imagenes,
        ]);
    }

    /**
     * Crear nueva galería
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $gallery = Gallery::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'] ?? 0,
            'activo' => $validated['activo'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $gallery->id,
                'nombre' => $gallery->nombre,
                'descripcion' => $gallery->descripcion,
                'orden' => $gallery->orden,
                'activo' => $gallery->activo,
                'imagenes' => [],
            ],
        ], 201);
    }

    /**
     * Actualizar galería
     */
    public function update(Request $request, Gallery $gallery): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $gallery->update($validated);

        return response()->json(['success' => true, 'message' => 'Galería actualizada']);
    }

    /**
     * Eliminar galería
     */
    public function destroy(Gallery $gallery): JsonResponse
    {
        $imagenes = $gallery->imagenes()->get();
        foreach ($imagenes as $img) {
            if ($img->imagen) {
                Storage::disk('public')->delete($img->imagen);
            }
        }

        $gallery->delete();

        return response()->json(['success' => true, 'message' => 'Galería eliminada']);
    }

    /**
     * Agregar imagen a galería
     */
    public function addImage(Request $request, Gallery $gallery): JsonResponse
    {
        // Validar límite de 8 imágenes por galería
        if ($gallery->imagenes()->count() >= 8) {
            return response()->json([
                'success' => false,
                'message' => 'Máximo 8 imágenes por galería',
            ], 422);
        }

        $validated = $request->validate([
            'imagen' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:max_width=4000'],
            'aspect_ratio' => ['nullable', 'string', 'max:20'],
            'orden' => ['nullable', 'integer'],
        ]);

        $path = $request->file('imagen')->store('galleries', 'public');

        $imagen = $gallery->imagenes()->create([
            'imagen' => $path,
            'aspect_ratio' => $validated['aspect_ratio'] ?? '1/1',
            'orden' => $validated['orden'] ?? (int)$gallery->imagenes()->max('orden') + 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $imagen->id,
                'imagen_url' => $this->mediaUrl($imagen->imagen),
                'aspect_ratio' => $imagen->aspect_ratio,
                'orden' => $imagen->orden,
                'productos' => [],
            ],
        ], 201);
    }

    /**
     * Eliminar imagen de galería
     */
    public function deleteImage(Gallery $gallery, GalleryImage $imagen): JsonResponse
    {
        if ($imagen->gallery_id !== $gallery->id) {
            return response()->json(['success' => false, 'message' => 'Imagen no pertenece a esta galería'], 404);
        }

        if ($imagen->imagen) {
            Storage::disk('public')->delete($imagen->imagen);
        }

        $imagen->delete();

        return response()->json(['success' => true, 'message' => 'Imagen eliminada']);
    }

    /**
     * Actualizar metadatos de imagen (aspect_ratio, orden)
     */
    public function updateImage(Request $request, Gallery $gallery, GalleryImage $imagen): JsonResponse
    {
        if ($imagen->gallery_id !== $gallery->id) {
            return response()->json(['success' => false, 'message' => 'Imagen no pertenece a esta galería'], 404);
        }

        $validated = $request->validate([
            'aspect_ratio' => ['nullable', 'string', 'max:20'],
            'orden' => ['nullable', 'integer'],
        ]);

        $imagen->update($validated);

        return response()->json(['success' => true, 'message' => 'Imagen actualizada']);
    }

    /**
     * Asociar producto a imagen de galería
     */
    public function associateProduct(Request $request, Gallery $gallery, GalleryImage $imagen): JsonResponse
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
        ]);

        if ($imagen->gallery_id !== $gallery->id) {
            return response()->json(['success' => false, 'message' => 'Imagen no pertenece a esta galería'], 404);
        }

        $producto = Producto::findOrFail($validated['producto_id']);

        $gip = GalleryImageProduct::updateOrCreate(
            [
                'gallery_image_id' => $imagen->id,
                'producto_id' => $producto->id,
            ],
            [
                'orden' => $imagen->productos()->max('orden') + 1,
            ]
        );

        return response()->json([
            'success' => true,
            'producto' => [
                'id' => $gip->id,
                'producto_id' => $producto->id,
                'referencia' => $producto->referencia,
                'nombre' => $producto->nombre,
                'precio' => (float) $producto->precio,
                'foto' => $this->mediaUrl($producto->foto),
            ],
        ]);
    }

    /**
     * Desasociar producto de imagen
     */
    public function disassociateProduct(Gallery $gallery, GalleryImage $imagen, GalleryImageProduct $gip): JsonResponse
    {
        if ($imagen->gallery_id !== $gallery->id || $gip->gallery_image_id !== $imagen->id) {
            return response()->json(['success' => false, 'message' => 'Recurso no válido'], 404);
        }

        $gip->delete();

        return response()->json(['success' => true, 'message' => 'Producto desasociado']);
    }
}
