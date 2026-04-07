<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\Request;

class SlideController extends Controller
{
    public function index()
    {
        $slides = Slide::getActivos();
        return response()->json(['success' => true, 'data' => $slides], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'imagen' => 'required|string',
            'imagen_mobile' => 'nullable|string',
            'boton_texto' => 'nullable|string',
            'boton_url' => 'nullable|string',
            'estilo' => 'nullable|string|in:dark,light,transparent',
            'orden' => 'integer|min:0',
            'esta_activo' => 'boolean',
        ]);

        $slide = Slide::create($validated);
        return response()->json(['success' => true, 'data' => $slide], 201);
    }

    public function show($id)
    {
        $slide = Slide::find($id);
        if (!$slide) {
            return response()->json(['success' => false, 'message' => 'Slide no encontrado'], 404);
        }
        return response()->json(['success' => true, 'data' => $slide], 200);
    }

    public function update(Request $request, $id)
    {
        $slide = Slide::find($id);
        if (!$slide) {
            return response()->json(['success' => false, 'message' => 'Slide no encontrado'], 404);
        }

        $validated = $request->validate([
            'titulo' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'imagen' => 'sometimes|string',
            'imagen_mobile' => 'nullable|string',
            'boton_texto' => 'nullable|string',
            'boton_url' => 'nullable|string',
            'estilo' => 'nullable|string|in:dark,light,transparent',
            'orden' => 'integer|min:0',
            'esta_activo' => 'boolean',
        ]);

        $slide->update($validated);
        return response()->json(['success' => true, 'data' => $slide], 200);
    }

    public function destroy($id)
    {
        $slide = Slide::find($id);
        if (!$slide) {
            return response()->json(['success' => false, 'message' => 'Slide no encontrado'], 404);
        }

        $slide->delete();
        return response()->json(['success' => true, 'message' => 'Slide eliminado'], 200);
    }
}