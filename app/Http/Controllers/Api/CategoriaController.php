<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * GET /api/categorias - Listar todas las categorías
     */
    public function index()
    {
        $categorias = Categoria::orderBy('categoria')->get();

        return response()->json([
            'success' => true,
            'data' => $categorias,
            'message' => 'OK'
        ], 200);
    }

    /**
     * GET /api/categorias/{id} - Ver una categoría específica
     */
    public function show($id)
    {
        $categoria = Categoria::with('productos')->find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $categoria,
            'message' => 'OK'
        ], 200);
    }

    /**
     * POST /api/categorias - Crear una categoría (admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria' => 'required|string|unique:categorias',
            'tipoDePrenda' => 'nullable|string'
        ]);

        $categoria = Categoria::create($validated);

        return response()->json([
            'success' => true,
            'data' => $categoria,
            'message' => 'Categoría creada correctamente'
        ], 201);
    }

    /**
     * PUT /api/categorias/{id} - Actualizar una categoría (admin)
     */
    public function update(Request $request, $id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $validated = $request->validate([
            'categoria' => 'sometimes|string|unique:categorias,categoria,' . $id,
            'tipoDePrenda' => 'nullable|string'
        ]);

        $categoria->update($validated);

        return response()->json([
            'success' => true,
            'data' => $categoria,
            'message' => 'Categoría actualizada correctamente'
        ], 200);
    }

    /**
     * DELETE /api/categorias/{id} - Eliminar una categoría (admin)
     */
    public function destroy($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente'
        ], 200);
    }
}