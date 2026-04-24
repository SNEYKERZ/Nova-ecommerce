<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloqueHome;
use App\Models\BloqueHomeImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BloqueHomeController extends Controller
{
    /**
     * GET /api/bloques-home - Obtener los dos bloques para el home
     */
    public function index()
    {
        $bloques = [
            BloqueHome::getPorPosicion(1),
            BloqueHome::getPorPosicion(2),
        ];

        $data = collect($bloques)->map(function ($bloque) {
            if (!$bloque) {
                return null;
            }

            return [
                'id' => $bloque->id,
                'tipo' => $bloque->tipo,
                'posicion' => $bloque->posicion,
                'titulo' => $bloque->titulo,
                'contenido' => $bloque->contenido,
                'tamano_texto' => $bloque->tamano_texto,
                'activo' => $bloque->activo,
                'imagenes' => $bloque->tipo === 'banner' 
                    ? $bloque->imagenes->map(fn($img) => [
                        'id' => $img->id,
                        'imagen' => asset('storage/' . $img->imagen),
                        'url_destino' => $img->url_destino,
                        'orden' => $img->orden,
                    ])
                    : [],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/bloques-home - Crear o actualizar bloque
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'posicion' => 'required|integer|min:1|max:2',
            'tipo' => 'required|in:banner,texto',
            'titulo' => 'nullable|string|max:255',
            'contenido' => 'nullable|string',
            'tamano_texto' => 'nullable|in:normal,grande',
            'activo' => 'nullable|boolean',
        ]);

        // Buscar bloque existente o crear nuevo
        $bloque = BloqueHome::getPorPosicion($validated['posicion']);
        
        if ($bloque) {
            $bloque->update([
                'tipo' => $validated['tipo'],
                'titulo' => $validated['titulo'] ?? null,
                'contenido' => $validated['contenido'] ?? null,
                'tamano_texto' => $validated['tamano_texto'] ?? 'normal',
                'activo' => $validated['activo'] ?? true,
            ]);
        } else {
            $bloque = BloqueHome::create([
                'posicion' => $validated['posicion'],
                'tipo' => $validated['tipo'],
                'titulo' => $validated['titulo'] ?? null,
                'contenido' => $validated['contenido'] ?? null,
                'tamano_texto' => $validated['tamano_texto'] ?? 'normal',
                'activo' => $validated['activo'] ?? true,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $bloque,
            'message' => 'Bloque guardado correctamente',
        ]);
    }

    /**
     * POST /api/bloques-home/{id}/imagenes - Agregar imagen a bloque banner
     */
    public function storeImagen(Request $request, int $id)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'url_destino' => 'nullable|url',
        ]);

        $bloque = BloqueHome::findOrFail($id);

        if ($bloque->tipo !== 'banner') {
            return response()->json([
                'success' => false,
                'message' => 'Este bloque es de tipo texto, no admite imágenes',
            ], 422);
        }

        // Verificar límite de 4 imágenes
        if ($bloque->imagenes()->count() >= 4) {
            return response()->json([
                'success' => false,
                'message' => 'Máximo 4 imágenes por bloque',
            ], 422);
        }

        $imagen = $request->file('imagen')->store('bloques', 'public');

        $bloqueImg = BloqueHomeImagen::create([
            'bloque_home_id' => $bloque->id,
            'imagen' => $imagen,
            'url_destino' => $request->input('url_destino'),
            'orden' => $bloque->imagenes()->max('orden') + 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $bloqueImg->id,
                'imagen' => asset('storage/' . $bloqueImg->imagen),
                'url_destino' => $bloqueImg->url_destino,
                'orden' => $bloqueImg->orden,
            ],
            'message' => 'Imagen agregada',
        ]);
    }

    /**
     * PUT /api/bloques-home/{id}/imagenes/{imgId} - Actualizar imagen
     */
    public function updateImagen(Request $request, int $id, int $imgId)
    {
        $request->validate([
            'url_destino' => 'nullable|url',
            'orden' => 'nullable|integer|min:1',
        ]);

        $imagen = BloqueHomeImagen::where('bloque_home_id', $id)->findOrFail($imgId);

        if ($request->has('url_destino')) {
            $imagen->url_destino = $request->input('url_destino');
        }
        
        if ($request->has('orden')) {
            $imagen->orden = $request->input('orden');
        }

        $imagen->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $imagen->id,
                'imagen' => asset('storage/' . $imagen->imagen),
                'url_destino' => $imagen->url_destino,
                'orden' => $imagen->orden,
            ],
            'message' => 'Imagen actualizada',
        ]);
    }

    /**
     * DELETE /api/bloques-home/{id}/imagenes/{imgId} - Eliminar imagen
     */
    public function destroyImagen(int $id, int $imgId)
    {
        $imagen = BloqueHomeImagen::where('bloque_home_id', $id)->findOrFail($imgId);
        
        // Eliminar archivo
        Storage::disk('public')->delete($imagen->imagen);
        
        $imagen->delete();

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada',
        ]);
    }
}