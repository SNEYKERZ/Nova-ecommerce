<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plantilla;
use Illuminate\Http\Request;

class PlantillaController extends Controller
{
    public function index()
    {
        $plantillas = Plantilla::orderBy('nombre')->get();
        return response()->json(['success' => true, 'data' => $plantillas], 200);
    }

    public function activa()
    {
        $plantilla = Plantilla::getActiva();
        return response()->json(['success' => true, 'data' => $plantilla], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string',
            'slug' => 'required|string|unique:plantillas',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'es_principal' => 'boolean',
            'esta_activa' => 'boolean',
            'configuracion' => 'nullable|array',
        ]);

        $plantilla = Plantilla::create($validated);
        return response()->json(['success' => true, 'data' => $plantilla], 201);
    }

    public function show($id)
    {
        $plantilla = Plantilla::with('banners')->find($id);
        if (!$plantilla) {
            return response()->json(['success' => false, 'message' => 'Plantilla no encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $plantilla], 200);
    }

    public function update(Request $request, $id)
    {
        $plantilla = Plantilla::find($id);
        if (!$plantilla) {
            return response()->json(['success' => false, 'message' => 'Plantilla no encontrada'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string',
            'slug' => 'sometimes|string|unique:plantillas,slug,' . $id,
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'es_principal' => 'boolean',
            'esta_activa' => 'boolean',
            'configuracion' => 'nullable|array',
        ]);

        $plantilla->update($validated);
        return response()->json(['success' => true, 'data' => $plantilla], 200);
    }

    public function destroy($id)
    {
        $plantilla = Plantilla::find($id);
        if (!$plantilla) {
            return response()->json(['success' => false, 'message' => 'Plantilla no encontrada'], 404);
        }

        $plantilla->delete();
        return response()->json(['success' => true, 'message' => 'Plantilla eliminada'], 200);
    }
}