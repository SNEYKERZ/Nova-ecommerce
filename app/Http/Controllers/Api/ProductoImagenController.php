<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductoImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductoImagenController extends Controller
{
    /**
     * GET /api/productos/{producto}/imagenes
     */
    public function index($productoId)
    {
        $imagenes = ProductoImagen::where('producto_id', $productoId)
            ->orderBy('orden')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $imagenes,
            'message' => 'OK'
        ], 200);
    }

    /**
     * POST /api/productos/{producto}/imagenes
     * Agregar imagen (max 5 por producto)
     */
    public function store(Request $request, $productoId)
    {
        // Verificar límite de 5 imágenes
        $cantidadActual = ProductoImagen::where('producto_id', $productoId)->count();
        
        if ($cantidadActual >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Máximo 5 imágenes permitidas por producto'
            ], 422);
        }

        $validated = $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'es_principal' => 'nullable|boolean',
            'orden' => 'nullable|integer|min:0'
        ]);

        // Si se marca como principal, desmarcar los demás
        if ($validated['es_principal'] ?? false) {
            ProductoImagen::where('producto_id', $productoId)
                ->update(['es_principal' => false]);
        }

        // Guardar imagen
        $ruta = $request->file('imagen')->store('productos/' . $productoId, 'public');

        $imagen = ProductoImagen::create([
            'producto_id' => $productoId,
            'imagen' => $ruta,
            'es_principal' => $validated['es_principal'] ?? ($cantidadActual === 0),
            'orden' => $validated['orden'] ?? $cantidadActual
        ]);

        return response()->json([
            'success' => true,
            'data' => $imagen,
            'message' => 'Imagen agregada correctamente'
        ], 201);
    }

    /**
     * PUT /api/imagenes/{id}
     * Actualizar orden o principal
     */
    public function update(Request $request, $id)
    {
        $imagen = ProductoImagen::find($id);

        if (!$imagen) {
            return response()->json([
                'success' => false,
                'message' => 'Imagen no encontrada'
            ], 404);
        }

        $validated = $request->validate([
            'es_principal' => 'nullable|boolean',
            'orden' => 'nullable|integer|min:0'
        ]);

        // Si se marca como principal, desmarcar los demás
        if (isset($validated['es_principal']) && $validated['es_principal']) {
            ProductoImagen::where('producto_id', $imagen->producto_id)
                ->where('id', '!=', $id)
                ->update(['es_principal' => false]);
        }

        $imagen->update($validated);

        return response()->json([
            'success' => true,
            'data' => $imagen,
            'message' => 'Imagen actualizada correctamente'
        ], 200);
    }

    /**
     * DELETE /api/imagenes/{id}
     */
    public function destroy($id)
    {
        $imagen = ProductoImagen::find($id);

        if (!$imagen) {
            return response()->json([
                'success' => false,
                'message' => 'Imagen no encontrada'
            ], 404);
        }

        // Eliminar archivo
        if ($imagen->imagen && Storage::disk('public')->exists($imagen->imagen)) {
            Storage::disk('public')->delete($imagen->imagen);
        }

        $esPrincipal = $imagen->es_principal;
        $productoId = $imagen->producto_id;
        
        $imagen->delete();

        // Si era principal, asignar otro
        if ($esPrincipal) {
            $nuevaPrincipal = ProductoImagen::where('producto_id', $productoId)->first();
            if ($nuevaPrincipal) {
                $nuevaPrincipal->update(['es_principal' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente'
        ], 200);
    }
}