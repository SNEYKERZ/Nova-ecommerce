<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::getActivos();
        return response()->json(['success' => true, 'data' => $banners], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string',
            'descripcion' => 'nullable|string',
            'imagen' => 'required|string',
            'imagen_mobile' => 'nullable|string',
            'url_destino' => 'nullable|string',
            'plantilla_id' => 'nullable|exists:plantillas,id',
            'orden' => 'integer|min:0',
            'esta_activo' => 'boolean',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $banner = Banner::create($validated);
        return response()->json(['success' => true, 'data' => $banner], 201);
    }

    public function show($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner no encontrado'], 404);
        }
        return response()->json(['success' => true, 'data' => $banner], 200);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner no encontrado'], 404);
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|string',
            'descripcion' => 'nullable|string',
            'imagen' => 'sometimes|string',
            'imagen_mobile' => 'nullable|string',
            'url_destino' => 'nullable|string',
            'plantilla_id' => 'nullable|exists:plantillas,id',
            'orden' => 'integer|min:0',
            'esta_activo' => 'boolean',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $banner->update($validated);
        return response()->json(['success' => true, 'data' => $banner], 200);
    }

    public function destroy($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner no encontrado'], 404);
        }

        $banner->delete();
        return response()->json(['success' => true, 'message' => 'Banner eliminado'], 200);
    }
}