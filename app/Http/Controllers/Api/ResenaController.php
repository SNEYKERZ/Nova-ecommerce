<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resena;
use Illuminate\Http\Request;

class ResenaController extends Controller
{
    public function index($productoId)
    {
        $resenas = Resena::where('producto_id', $productoId)
            ->where('aprobada', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $resenas], 200);
    }

    public function store(Request $request, $productoId)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email',
            'calificacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
        ]);

        $validated['producto_id'] = $productoId;
        $validated['aprobada'] = false; // Requiere moderación

        $resena = Resena::create($validated);

        return response()->json([
            'success' => true,
            'data' => $resena,
            'message' => 'Reseña enviada. Será visible tras aprobación.'
        ], 201);
    }

    // Admin: ver reseñas pendientes
    public function pendientes()
    {
        $resenas = Resena::where('aprobada', false)
            ->with('producto')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $resenas], 200);
    }

    // Admin: aprobar reseña
    public function aprobar($id)
    {
        $resena = Resena::find($id);
        if (!$resena) {
            return response()->json(['success' => false, 'message' => 'Reseña no encontrada'], 404);
        }

        $resena->update(['aprobada' => true]);

        return response()->json(['success' => true, 'data' => $resena], 200);
    }
}