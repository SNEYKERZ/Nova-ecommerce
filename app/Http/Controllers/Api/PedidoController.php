<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\Cupon;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with(['cliente', 'items.producto'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $pedidos]);
    }

    public function show($id)
    {
        $pedido = Pedido::with(['cliente', 'items.producto'])->find($id);

        if (!$pedido) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);
        }

        return response()->json(['success' => true, 'data' => $pedido]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'                  => 'required|string|max:255',
            'apellidos'               => 'nullable|string|max:255',
            'email'                   => 'required|email',
            'telefono'                => 'nullable|string',
            'direccion'               => 'required|string',
            'items'                   => 'required|array|min:1',
            'items.*.producto_id'     => 'required|exists:productos,id',
            'items.*.cantidad'        => 'required|integer|min:1',
            'items.*.talla'           => 'nullable|string',
        ]);

        // Verificar stock disponible antes de crear el pedido
        foreach ($validated['items'] as $item) {
            if (!empty($item['talla'])) {
                $variante = ProductoVariante::where('producto_id', $item['producto_id'])
                    ->where('talla', $item['talla'])
                    ->first();

                if ($variante && $variante->stock < $item['cantidad']) {
                    $producto = Producto::find($item['producto_id']);
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuficiente para "' . ($producto?->nombre ?? $producto?->referencia) . '" talla ' . $item['talla'] . '. Disponible: ' . $variante->stock,
                    ], 422);
                }
            }
        }

        try {
            DB::beginTransaction();

            $cliente = Cliente::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'nombre'    => $validated['nombre'],
                    'apellidos' => $validated['apellidos'] ?? '',
                    'telefono'  => $validated['telefono'] ?? '',
                    'direccion' => $validated['direccion'],
                ]
            );

            $total = 0;
            $itemsParaCrear = [];

            foreach ($validated['items'] as $item) {
                $producto  = Producto::find($item['producto_id']);
                $subtotal  = $producto->precio * $item['cantidad'];
                $total    += $subtotal;

                $itemsParaCrear[] = [
                    'producto_id'    => $item['producto_id'],
                    'cantidad'       => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal'       => $subtotal,
                    'talla'          => $item['talla'] ?? null,
                ];
            }

            // Aplicar descuento de cupón si el carrito tiene uno
            $descuentoCupon = 0;
            $cuponId = null;
            try {
                $carrito = Carrito::getOrCreateCarrito();
                if ($carrito->cupon_id && $carrito->descuento_cupon > 0) {
                    $cupon = Cupon::find($carrito->cupon_id);
                    if ($cupon && $cupon->esValido($total)) {
                        $descuentoCupon = (float) $carrito->descuento_cupon;
                        $cuponId = $cupon->id;
                        $total = max(0, $total - $descuentoCupon);
                    }
                }
            } catch (\Exception $e) {
                // Si falla la lectura del carrito, continuar sin cupón
            }

            $pedido = Pedido::create([
                'cliente_id'    => $cliente->id,
                'total'         => $total,
                'estado'        => 'PENDIENTE',
                'direccion_envio' => $validated['direccion'],
                'cupon_id'      => $cuponId,
                'descuento_cupon' => $descuentoCupon,
            ]);

            // Incrementar usos del cupón
            if ($cuponId) {
                Cupon::where('id', $cuponId)->increment('usos_actuales');
            }

            foreach ($itemsParaCrear as $item) {
                PedidoItem::create([
                    'pedido_id'      => $pedido->id,
                    'producto_id'    => $item['producto_id'],
                    'cantidad'       => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal'       => $item['subtotal'],
                    'talla'          => $item['talla'],
                ]);

                // Decrementar stock si aplica variante
                if (!empty($item['talla'])) {
                    ProductoVariante::where('producto_id', $item['producto_id'])
                        ->where('talla', $item['talla'])
                        ->decrement('stock', $item['cantidad']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $pedido->load(['cliente', 'items.producto']),
                'message' => 'Pedido creado correctamente',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateEstado(Request $request, $id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);
        }

        $validated = $request->validate([
            'estado' => 'required|in:PENDIENTE,CONFIRMADO,ENVIADO,ENTREGADO,CANCELADO',
        ]);

        $pedido->update(['estado' => $validated['estado']]);

        return response()->json(['success' => true, 'data' => $pedido, 'message' => 'Estado actualizado']);
    }
}
