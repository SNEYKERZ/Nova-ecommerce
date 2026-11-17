<?php

namespace App\Http\Controllers\Api;

use App\Events\CarritoActualizado;
use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Cupon;
use App\Models\Producto;
use App\Services\CheckoutService;
use App\Validation\Rules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarritoController extends Controller
{
    private const MAX_CANTIDAD = 999;

    /**
     * GET /api/carrito - Obtener el carrito actual
     */
    public function show(): JsonResponse
    {
        $carrito = $this->carritoConItems();
        $cupon = $carrito->cuponVigente();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $carrito->id,
                'items' => $carrito->items->map(function (CarritoItem $item) {
                    return [
                        'id' => $item->id,
                        'producto' => [
                            'id' => $item->producto->id,
                            'referencia' => $item->producto->referencia,
                            'nombre' => $item->producto->nombre,
                            // Precio vigente (con oferta) calculado en servidor
                            'precio' => $item->precio_unitario,
                            'precio_original' => (float) $item->producto->precio,
                            'foto' => $item->producto->foto,
                            'tallas' => $item->producto->tallas,
                            'categoria' => $item->producto->categoria?->categoria,
                        ],
                        'cantidad' => $item->cantidad,
                        'talla' => $item->talla,
                        'subtotal' => $item->subtotal,
                    ];
                })->values(),
                'subtotal' => (float) $carrito->total,
                'total' => $carrito->totalConDescuento(),
                'descuento_cupon' => $carrito->descuentoVigente(),
                'cupon' => $cupon ? [
                    'id' => $cupon->id,
                    'codigo' => $cupon->codigo,
                    'tipo' => $cupon->tipo,
                    'valor_descuento' => (float) $cupon->valor_descuento,
                ] : null,
                'cantidad_total' => $carrito->cantidad_total,
            ],
            'message' => 'OK',
        ], 200);
    }

    /**
     * POST /api/carrito/agregar - Agregar producto al carrito
     */
    public function agregar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'integer', Rules::existsInTenant('productos')],
            'cantidad' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_CANTIDAD],
            'talla' => ['nullable', 'string', 'max:40'],
        ]);

        $producto = Producto::findOrFail($validated['producto_id']);

        if ($producto->estado !== 'DISPONIBLE') {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no está disponible.',
            ], 422);
        }

        $carrito = Carrito::getOrCreateCarrito();
        $talla = isset($validated['talla']) && trim($validated['talla']) !== '' ? trim($validated['talla']) : null;
        $cantidad = (int) ($validated['cantidad'] ?? 1);

        // Verificar si el producto ya está en el carrito
        $itemExistente = CarritoItem::where('carrito_id', $carrito->id)
            ->where('producto_id', $producto->id)
            ->where('talla', $talla)
            ->first();

        if ($itemExistente) {
            $itemExistente->cantidad = min(self::MAX_CANTIDAD, $itemExistente->cantidad + $cantidad);
            $itemExistente->save();
            $item = $itemExistente;
        } else {
            $item = CarritoItem::create([
                'carrito_id' => $carrito->id,
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'talla' => $talla,
            ]);
        }

        $carrito->load(['items.producto.categoria']);

        event(new CarritoActualizado($carrito));

        return response()->json([
            'success' => true,
            'data' => [
                'carrito' => $carrito,
                'item' => $item,
            ],
            'message' => 'Producto agregado al carrito',
        ], 200);
    }

    /**
     * PUT /api/carrito/actualizar/{id} - Actualizar cantidad o talla
     * Sólo sobre items del carrito de la sesión actual.
     */
    public function actualizar(Request $request, $id): JsonResponse
    {
        $carrito = Carrito::getOrCreateCarrito();
        $item = CarritoItem::where('carrito_id', $carrito->id)->find($id);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado',
            ], 404);
        }

        $validated = $request->validate([
            'cantidad' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_CANTIDAD],
            'talla' => ['nullable', 'string', 'max:40'],
        ]);

        if (isset($validated['cantidad'])) {
            if ((int) $validated['cantidad'] === 0) {
                $item->delete();
            } else {
                $item->cantidad = $validated['cantidad'];
                $item->save();
            }
        }

        if (isset($validated['talla']) && $item->exists) {
            $item->talla = trim($validated['talla']) !== '' ? trim($validated['talla']) : null;
            $item->save();
        }

        $carrito->load(['items.producto.categoria']);

        event(new CarritoActualizado($carrito));

        return response()->json([
            'success' => true,
            'data' => $carrito,
            'message' => 'Carrito actualizado',
        ], 200);
    }

    /**
     * DELETE /api/carrito/eliminar/{id} - Eliminar item del carrito
     * Sólo sobre items del carrito de la sesión actual.
     */
    public function eliminar($id): JsonResponse
    {
        $carrito = Carrito::getOrCreateCarrito();
        $item = CarritoItem::where('carrito_id', $carrito->id)->find($id);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado',
            ], 404);
        }

        $item->delete();

        $carrito->load(['items.producto.categoria']);

        event(new CarritoActualizado($carrito));

        return response()->json([
            'success' => true,
            'data' => $carrito,
            'message' => 'Item eliminado del carrito',
        ], 200);
    }

    /**
     * DELETE /api/carrito/vaciar - Vaciar todo el carrito
     */
    public function vaciar(): JsonResponse
    {
        $carrito = Carrito::getOrCreateCarrito();
        $carrito->items()->delete();
        $carrito->update(['cupon_id' => null, 'descuento_cupon' => null]);

        event(new CarritoActualizado($carrito));

        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado',
        ], 200);
    }

    /**
     * POST /api/carrito/aplicar-cupon - Aplicar un cupón de descuento
     */
    public function aplicarCupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50'],
        ]);

        $carrito = $this->carritoConItems();

        $codigo = mb_strtoupper(trim($validated['codigo']));
        $cupon = Cupon::where('codigo', $codigo)->first();

        if (! $cupon) {
            return response()->json([
                'success' => false,
                'message' => 'El código ingresado no es válido.',
            ], 422);
        }

        $subtotal = (float) $carrito->total; // sin descuentos todavía

        if ($carrito->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Agrega productos al carrito antes de aplicar un cupón.',
            ], 422);
        }

        if (! $cupon->esValido($subtotal)) {
            if (! $cupon->esta_activo) {
                $msg = 'Este cupón no está disponible.';
            } elseif ($cupon->estaExpirado()) {
                $msg = 'Este cupón ha expirado.';
            } elseif ($cupon->max_usos && $cupon->usos_actuales >= $cupon->max_usos) {
                $msg = 'Este cupón ya no tiene usos disponibles.';
            } elseif ($cupon->monto_minimo && $subtotal < $cupon->monto_minimo) {
                $msg = 'El monto mínimo para este cupón es '.number_format((float) $cupon->monto_minimo, 0).'.';
            } else {
                $msg = 'El cupón no es válido para esta compra.';
            }

            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 422);
        }

        $descuento = $cupon->calcularDescuento($subtotal);

        // descuento_cupon queda como referencia; el valor efectivo se
        // recalcula siempre contra el subtotal vigente.
        $carrito->update([
            'cupon_id' => $cupon->id,
            'descuento_cupon' => $descuento,
        ]);

        $carrito->load('cupon');

        return response()->json([
            'success' => true,
            'data' => [
                'descuento_cupon' => (float) $descuento,
                'cupon' => [
                    'id' => $cupon->id,
                    'codigo' => $cupon->codigo,
                    'tipo' => $cupon->tipo,
                    'valor_descuento' => (float) $cupon->valor_descuento,
                ],
                'total' => $carrito->totalConDescuento(),
            ],
            'message' => 'Cupón aplicado correctamente.',
        ], 200);
    }

    /**
     * DELETE /api/carrito/quitar-cupon - Quitar el cupón aplicado
     */
    public function quitarCupon(): JsonResponse
    {
        $carrito = Carrito::getOrCreateCarrito();
        $carrito->update([
            'cupon_id' => null,
            'descuento_cupon' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cupón quitado correctamente.',
        ], 200);
    }

    /**
     * POST /api/carrito/pedir-whatsapp - Crear pedido desde carrito y abrir WhatsApp
     *
     * Usa el mismo flujo que el checkout web: precios/cupón en servidor y
     * stock validado y descontado de forma atómica.
     */
    public function pedirWhatsapp(Request $request, CheckoutService $checkout): JsonResponse
    {
        $carrito = Carrito::getOrCreateCarrito();
        $carrito->load('items');

        if ($carrito->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito está vacío.',
            ], 422);
        }

        $lineas = $carrito->items->map(fn (CarritoItem $item) => [
            'producto_id' => $item->producto_id,
            'cantidad' => $item->cantidad,
            'talla' => $item->talla,
        ])->all();

        $pedido = $checkout->crearPedido($lineas, [
            'origen' => 'whatsapp',
            'direccion_envio' => 'Por WhatsApp',
        ], $carrito);

        PedidoController::recordarPedidoEnSesion($request, $pedido->id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pedido->id,
                'total' => (float) $pedido->total,
            ],
            'message' => 'Pedido creado. Redirigiendo a WhatsApp...',
        ], 201);
    }

    private function carritoConItems(): Carrito
    {
        $carrito = Carrito::getOrCreateCarrito();
        $carrito->load(['items.producto.categoria', 'items.producto.variantes', 'cupon']);

        // Items cuyo producto ya no existe (o no es de esta tienda) se descartan
        $carrito->setRelation('items', $carrito->items->filter(fn (CarritoItem $item) => $item->producto !== null)->values());

        return $carrito;
    }
}
