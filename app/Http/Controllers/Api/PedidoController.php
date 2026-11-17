<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Services\CheckoutService;
use App\Validation\Rules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    /** Clave de sesión con los pedidos creados por este visitante (thank-you page). */
    public const SESSION_KEY = 'pedidos_realizados';

    /**
     * POST /api/pedidos - Crear pedido desde el checkout.
     *
     * Del cliente sólo se usan producto_id, cantidad y talla. Precios, ofertas,
     * cupón y total se calculan en servidor; el stock se valida y descuenta de
     * forma atómica (ver CheckoutService).
     */
    public function store(Request $request, CheckoutService $checkout): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.producto_id' => ['required', 'integer', Rules::existsInTenant('productos')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.talla' => ['nullable', 'string', 'max:40'],
        ]);

        $carrito = Carrito::getOrCreateCarrito();

        $pedido = $checkout->crearPedido($validated['items'], [
            'origen' => 'web',
            'direccion_envio' => $validated['direccion'],
            'cliente' => [
                'nombre' => $validated['nombre'],
                'apellidos' => $validated['apellidos'] ?? '',
                'email' => $validated['email'],
                'telefono' => $validated['telefono'] ?? '',
                'direccion' => $validated['direccion'],
            ],
        ], $carrito);

        self::recordarPedidoEnSesion($request, $pedido->id);

        return response()->json([
            'success' => true,
            'data' => $pedido->load(['cliente', 'items.producto']),
            'message' => 'Pedido creado correctamente',
        ], 201);
    }

    /**
     * Guarda el ID del pedido en la sesión del visitante para que sólo él
     * pueda ver /pedido/gracias/{id}.
     */
    public static function recordarPedidoEnSesion(Request $request, int $pedidoId): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $pedidoId;

        // Conservar sólo los últimos 20
        $request->session()->put(self::SESSION_KEY, array_slice(array_values(array_unique($ids)), -20));
    }
}
