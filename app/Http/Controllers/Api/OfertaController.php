<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Oferta;
use Illuminate\Http\Request;

class OfertaController extends Controller
{
    public function index()
    {
        $ofertas = Oferta::with(['producto', 'categoria'])->get();
        return response()->json(['success' => true, 'data' => $ofertas], 200);
    }

    public function activas()
    {
        $ofertas = Oferta::getOfertasActivas()->load(['producto', 'categoria']);
        return response()->json(['success' => true, 'data' => $ofertas], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string',
            'descripcion' => 'nullable|string',
            'producto_id' => 'nullable|exists:productos,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_fijo' => 'nullable|numeric|min:0',
            'precio_oferta' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ]);

        $oferta = Oferta::create(array_merge($validated, ['esta_activa' => true]));

        return response()->json(['success' => true, 'data' => $oferta], 201);
    }

    public function show($id)
    {
        $oferta = Oferta::with(['producto', 'categoria'])->find($id);
        if (!$oferta) {
            return response()->json(['success' => false, 'message' => 'Oferta no encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $oferta], 200);
    }

    public function update(Request $request, $id)
    {
        $oferta = Oferta::find($id);
        if (!$oferta) {
            return response()->json(['success' => false, 'message' => 'Oferta no encontrada'], 404);
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|string',
            'descripcion' => 'nullable|string',
            'producto_id' => 'nullable|exists:productos,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_fijo' => 'nullable|numeric|min:0',
            'precio_oferta' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'sometimes|date|after:fecha_inicio',
            'esta_activa' => 'sometimes|boolean',
        ]);

        $oferta->update($validated);

        return response()->json(['success' => true, 'data' => $oferta], 200);
    }

    public function destroy($id)
    {
        $oferta = Oferta::find($id);
        if (!$oferta) {
            return response()->json(['success' => false, 'message' => 'Oferta no encontrada'], 404);
        }

        $oferta->delete();

        return response()->json(['success' => true, 'message' => 'Oferta eliminada'], 200);
    }
}