<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\PedidoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PedidoController extends Controller
{
    /**
     * GET /api/pedidos - Listar todos los pedidos (admin)
     */
    public function index()
    {
        $pedidos = Pedido::with(['cliente', 'items.producto'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pedidos,
            'message' => 'OK'
        ], 200);
    }

    /**
     * GET /api/pedidos/{id} - Ver un pedido específico
     */
    public function show($id)
    {
        $pedido = Pedido::with(['cliente', 'items.producto'])->find($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pedido,
            'message' => 'OK'
        ], 200);
    }

    /**
     * POST /api/pedidos - Crear un nuevo pedido
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'email' => 'required|email',
            'telefono' => 'nullable|string',
            'direccion' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.talla' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Crear o buscar cliente
            $cliente = Cliente::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'nombre' => $validated['nombre'],
                    'apellidos' => $validated['apellidos'] ?? '',
                    'telefono' => $validated['telefono'] ?? '',
                    'direccion' => $validated['direccion']
                ]
            );

            // Calcular total
            $total = 0;
            $itemsParaCrear = [];

            foreach ($validated['items'] as $item) {
                $producto = \App\Models\Producto::find($item['producto_id']);
                $subtotal = $producto->precio * $item['cantidad'];
                $total += $subtotal;

                $itemsParaCrear[] = [
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal,
                    'talla' => $item['talla'] ?? null
                ];
            }

            // Aplicar descuentos por cantidad (manteniendo la lógica original)
            $contadorCamisetas = 0;
            foreach ($itemsParaCrear as $item) {
                $producto = \App\Models\Producto::find($item['producto_id']);
                if ($producto->categoria && str_starts_with($producto->categoria->categoria, 'CAMISETA')) {
                    $contadorCamisetas += $item['cantidad'];
                }
            }

            if ($contadorCamisetas >= 2 && $contadorCamisetas <= 5) {
                if ($contadorCamisetas == 2) $total -= 5000;
                elseif ($contadorCamisetas == 3) $total -= 15000;
                elseif ($contadorCamisetas == 4) $total -= 25000;
                elseif ($contadorCamisetas == 5) $total -= 40000;
            }

            // Crear pedido
            $pedido = Pedido::create([
                'cliente_id' => $cliente->id,
                'total' => $total,
                'estado' => 'PENDIENTE',
                'direccion_envio' => $validated['direccion']
            ]);

            // Crear items del pedido
            foreach ($itemsParaCrear as $item) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                    'talla' => $item['talla']
                ]);
            }

            DB::commit();

            // Enviar email de confirmación (en cola para producción)
            // Mail::to($cliente->email)->send(new PedidoConfirmado($pedido));

            return response()->json([
                'success' => true,
                'data' => $pedido->load(['cliente', 'items.producto']),
                'message' => 'Pedido creado correctamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/pedidos/{id}/estado - Actualizar estado del pedido (admin)
     */
    public function updateEstado(Request $request, $id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'estado' => 'required|in:PENDIENTE,CONFIRMADO,ENVIADO,ENTREGADO,CANCELADO'
        ]);

        $pedido->update(['estado' => $validated['estado']]);

        return response()->json([
            'success' => true,
            'data' => $pedido,
            'message' => 'Estado actualizado correctamente'
        ], 200);
    }
}
