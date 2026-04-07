<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * GET /api/productos - Listar todos los productos
     */
    public function index()
    {
        $productos = Producto::with('categoria')
            ->where('estado', 'DISPONIBLE')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
            'message' => 'OK'
        ], 200);
    }

    /**
     * GET /api/productos/basicas - Listar solo camisetas básicas
     */
    public function basicas()
    {
        $productos = Producto::with('categoria')
            ->whereHas('categoria', function ($query) {
                $query->where('categoria', 'BASICA');
            })
            ->where('estado', 'DISPONIBLE')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
            'message' => 'OK'
        ], 200);
    }

    /**
     * GET /api/productos/{id} - Ver un producto específico
     */
    public function show($id)
    {
        $producto = Producto::with('categoria')->find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'OK'
        ], 200);
    }

    /**
     * POST /api/productos - Crear un producto (admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'referencia' => 'required|string|unique:productos',
            'precio' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'tallas' => 'nullable|string',
            'estado' => 'nullable|in:DISPONIBLE,NO_DISPONIBLE'
        ]);

        $producto = Producto::create($validated);

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'Producto creado correctamente'
        ], 201);
    }

    /**
     * PUT /api/productos/{id} - Actualizar un producto (admin)
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'referencia' => 'sometimes|string|unique:productos,referencia,' . $id,
            'precio' => 'sometimes|numeric|min:0',
            'categoria_id' => 'sometimes|exists:categorias,id',
            'tallas' => 'nullable|string',
            'estado' => 'sometimes|in:DISPONIBLE,NO_DISPONIBLE'
        ]);

        $producto->update($validated);

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'Producto actualizado correctamente'
        ], 200);
    }

    /**
     * DELETE /api/productos/{id} - Eliminar un producto (admin)
     */
    public function destroy($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ], 200);
    }
}