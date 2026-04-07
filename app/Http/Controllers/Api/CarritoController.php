<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Events\CarritoActualizado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarritoController extends Controller
{
    /**
     * GET /api/carrito - Obtener el carrito actual
     */
    public function show()
    {
        $carrito = Carrito::getOrCreateCarrito();
        
        $carrito->load(['items.producto.categoria']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $carrito->id,
                'items' => $carrito->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'producto' => [
                            'id' => $item->producto->id,
                            'referencia' => $item->producto->referencia,
                            'precio' => $item->producto->precio,
                            'foto' => $item->producto->foto,
                            'tallas' => $item->producto->tallas,
                            'categoria' => $item->producto->categoria->categoria
                        ],
                        'cantidad' => $item->cantidad,
                        'talla' => $item->talla,
                        'subtotal' => $item->subtotal
                    ];
                }),
                'total' => $this->calcularTotal($carrito),
                'cantidad_total' => $carrito->cantidad_total
            ],
            'message' => 'OK'
        ], 200);
    }

    /**
     * POST /api/carrito/agregar - Agregar producto al carrito
     */
    public function agregar(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'nullable|integer|min:1',
            'talla' => 'nullable|string'
        ]);

        $carrito = Carrito::getOrCreateCarrito();
        
        // Verificar si el producto ya está en el carrito
        $itemExistente = CarritoItem::where('carrito_id', $carrito->id)
            ->where('producto_id', $validated['producto_id'])
            ->where('talla', $validated['talla'] ?? null)
            ->first();

        if ($itemExistente) {
            // Incrementar cantidad
            $itemExistente->cantidad += $validated['cantidad'] ?? 1;
            $itemExistente->save();
            $item = $itemExistente;
        } else {
            // Crear nuevo item
            $item = CarritoItem::create([
                'carrito_id' => $carrito->id,
                'producto_id' => $validated['producto_id'],
                'cantidad' => $validated['cantidad'] ?? 1,
                'talla' => $validated['talla'] ?? null
            ]);
        }

        // Recargar relaciones
        $carrito->load(['items.producto.categoria']);

        // Emitir evento de actualización
        event(new CarritoActualizado($carrito));

        return response()->json([
            'success' => true,
            'data' => [
                'carrito' => $carrito,
                'item' => $item
            ],
            'message' => 'Producto agregado al carrito'
        ], 200);
    }

    /**
     * PUT /api/carrito/actualizar/{id} - Actualizar cantidad o talla
     */
    public function actualizar(Request $request, $id)
    {
        $item = CarritoItem::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'cantidad' => 'nullable|integer|min:0',
            'talla' => 'nullable|string'
        ]);

        if (isset($validated['cantidad'])) {
            if ($validated['cantidad'] == 0) {
                $item->delete();
            } else {
                $item->cantidad = $validated['cantidad'];
                $item->save();
            }
        }

        if (isset($validated['talla'])) {
            $item->talla = $validated['talla'];
            $item->save();
        }

        // Recargar carrito
        $carrito = Carrito::with(['items.producto.categoria'])->find($item->carrito_id);

        // Emitir evento de actualización
        event(new CarritoActualizado($carrito));

        return response()->json([
            'success' => true,
            'data' => $carrito,
            'message' => 'Carrito actualizado'
        ], 200);
    }

    /**
     * DELETE /api/carrito/eliminar/{id} - Eliminar item del carrito
     */
    public function eliminar($id)
    {
        $item = CarritoItem::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado'
            ], 404);
        }

        $carritoId = $item->carrito_id;
        $item->delete();

        // Recargar carrito
        $carrito = Carrito::with(['items.producto.categoria'])->find($carritoId);

        // Emitir evento de actualización
        event(new CarritoActualizado($carrito));

        return response()->json([
            'success' => true,
            'data' => $carrito,
            'message' => 'Item eliminado del carrito'
        ], 200);
    }

    /**
     * DELETE /api/carrito/vaciar - Vaciar todo el carrito
     */
    public function vaciar()
    {
        $carrito = Carrito::getOrCreateCarrito();
        $carrito->items()->delete();

        // Emitir evento de actualización
        event(new CarritoActualizado($carrito));

        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado'
        ], 200);
    }

    /**
     * Calcular total con descuentos por cantidad
     */
    private function calcularTotal($carrito)
    {
        $total = 0;
        $contadorCamisetas = 0;

        foreach ($carrito->items as $item) {
            $subtotal = $item->producto->precio * $item->cantidad;
            $total += $subtotal;

            // Contar camisetas para descuentos
            if ($item->producto->categoria && str_starts_with($item->producto->categoria->categoria, 'CAMISETA')) {
                $contadorCamisetas += $item->cantidad;
            }
        }

        // Aplicar descuentos por cantidad
        if ($contadorCamisetas >= 2 && $contadorCamisetas <= 5) {
            if ($contadorCamisetas == 2) $total -= 5000;
            elseif ($contadorCamisetas == 3) $total -= 15000;
            elseif ($contadorCamisetas == 4) $total -= 25000;
            elseif ($contadorCamisetas == 5) $total -= 40000;
        }

        return max(0, $total);
    }
}
