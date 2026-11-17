<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\Cupon;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

/**
 * Creación de pedidos (web y WhatsApp).
 *
 * El cliente sólo aporta producto_id, cantidad y talla: precios, ofertas,
 * descuento de cupón y total se calculan SIEMPRE en servidor. El stock por
 * variante y los usos del cupón se validan y descuentan dentro de una única
 * transacción con bloqueo de filas (lockForUpdate).
 */
class CheckoutService
{
    public function __construct(
        protected PricingService $pricing,
        protected InventarioService $inventario,
    ) {}

    /**
     * @param  array<int, array{producto_id:int|string, cantidad:int|string, talla?:string|null}>  $lineas
     * @param  array{origen?:string, direccion_envio?:string|null, cliente?:array<string, mixed>|null}  $datos
     */
    public function crearPedido(array $lineas, array $datos, ?Carrito $carrito = null): Pedido
    {
        $lineas = $this->normalizarLineas($lineas);

        if (empty($lineas)) {
            throw new CheckoutException('El carrito está vacío.');
        }

        return DB::transaction(function () use ($lineas, $datos, $carrito) {
            $items = [];
            $subtotal = 0.0;

            foreach ($lineas as $linea) {
                $producto = Producto::whereKey($linea['producto_id'])->first();

                if (! $producto || $producto->estado !== 'DISPONIBLE') {
                    $nombre = $producto ? ($producto->nombre ?: $producto->referencia) : 'seleccionado';
                    throw new CheckoutException("El producto \"{$nombre}\" ya no está disponible.");
                }

                $variante = $this->inventario->varianteParaVenta($producto, $linea['talla']);
                $nombre = (string) ($producto->nombre ?: $producto->referencia);

                if ($variante) {
                    $this->inventario->descontar($variante, $linea['cantidad'], $nombre);
                }

                $precioUnitario = $this->pricing->precioUnitario($producto, $variante);
                $lineaSubtotal = round($precioUnitario * $linea['cantidad'], 2);
                $subtotal += $lineaSubtotal;

                $items[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $lineaSubtotal,
                    'talla' => $variante ? $variante->talla : $linea['talla'],
                ];
            }

            $subtotal = round($subtotal, 2);
            [$cuponId, $descuento] = $this->consumirCupon($carrito, $subtotal);

            $cliente = null;
            if (! empty($datos['cliente'])) {
                $c = $datos['cliente'];
                $cliente = Cliente::updateOrCreate(
                    ['email' => mb_strtolower(trim($c['email']))],
                    [
                        'nombre' => $c['nombre'],
                        'apellidos' => $c['apellidos'] ?? '',
                        'telefono' => $c['telefono'] ?? '',
                        'direccion' => $c['direccion'] ?? '',
                    ]
                );
            }

            $pedido = Pedido::create([
                'cliente_id' => $cliente?->id,
                'total' => round(max(0, $subtotal - $descuento), 2),
                'estado' => 'PENDIENTE',
                'origen' => $datos['origen'] ?? 'web',
                'direccion_envio' => $datos['direccion_envio'] ?? null,
                'cupon_id' => $cuponId,
                'descuento_cupon' => $descuento,
            ]);
            $pedido->forceFill(['stock_descontado' => true])->save();

            foreach ($items as $item) {
                PedidoItem::create(['pedido_id' => $pedido->id] + $item);
            }

            if ($carrito) {
                $carrito->items()->delete();
                $carrito->update(['cupon_id' => null, 'descuento_cupon' => null]);
            }

            return $pedido;
        });
    }

    /**
     * Revalida el cupón del carrito contra el subtotal real y consume un uso
     * de forma atómica. Un cupón que dejó de ser válido simplemente no aplica.
     *
     * @return array{0: int|null, 1: float}
     */
    protected function consumirCupon(?Carrito $carrito, float $subtotal): array
    {
        if (! $carrito || ! $carrito->cupon_id) {
            return [null, 0.0];
        }

        $cupon = Cupon::whereKey($carrito->cupon_id)->lockForUpdate()->first();

        if (! $cupon || ! $cupon->esValido($subtotal)) {
            return [null, 0.0];
        }

        $descuento = round($cupon->calcularDescuento($subtotal), 2);
        if ($descuento <= 0) {
            return [null, 0.0];
        }

        $cupon->increment('usos_actuales');

        return [$cupon->id, $descuento];
    }

    /**
     * Agrupa líneas repetidas (mismo producto y talla) y descarta basura.
     *
     * @return array<int, array{producto_id:int, cantidad:int, talla:string|null}>
     */
    protected function normalizarLineas(array $lineas): array
    {
        $agrupadas = [];

        foreach ($lineas as $linea) {
            $productoId = (int) ($linea['producto_id'] ?? 0);
            $cantidad = (int) ($linea['cantidad'] ?? 0);
            $talla = isset($linea['talla']) && trim((string) $linea['talla']) !== '' ? trim((string) $linea['talla']) : null;

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            $key = $productoId.'|'.($talla ?? '');
            if (isset($agrupadas[$key])) {
                $agrupadas[$key]['cantidad'] += $cantidad;
            } else {
                $agrupadas[$key] = ['producto_id' => $productoId, 'cantidad' => $cantidad, 'talla' => $talla];
            }
        }

        // Orden estable para bloquear filas siempre en el mismo orden (evita deadlocks)
        ksort($agrupadas);

        return array_values($agrupadas);
    }
}
