<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;

/**
 * Control de stock por variante (talla).
 *
 * Reglas:
 * - Un producto SIN variantes no lleva control de stock (se considera disponible).
 * - Un producto CON variantes exige talla válida y stock suficiente.
 * - Todas las operaciones deben llamarse dentro de una transacción: las
 *   variantes se leen con lockForUpdate() para que dos compras simultáneas
 *   no vendan la misma unidad.
 * - pedidos.stock_descontado indica si el pedido tiene stock reservado, para
 *   no descontar dos veces y poder reponer al cancelar.
 */
class InventarioService
{
    /**
     * Variante a vender para el producto/talla (bloqueada), o null si el
     * producto no maneja stock por variantes.
     */
    public function varianteParaVenta(Producto $producto, ?string $talla): ?ProductoVariante
    {
        $query = ProductoVariante::where('producto_id', $producto->id);

        if (! (clone $query)->exists()) {
            return null;
        }

        $nombre = $this->nombre($producto);
        $talla = $talla !== null ? trim($talla) : null;

        if ($talla === null || $talla === '') {
            throw new CheckoutException("Selecciona una talla para \"{$nombre}\".");
        }

        $variante = $query->where('talla', $talla)
            ->where('esta_activa', true)
            ->lockForUpdate()
            ->first();

        if (! $variante) {
            throw new CheckoutException("La talla {$talla} no está disponible para \"{$nombre}\".");
        }

        return $variante;
    }

    public function descontar(ProductoVariante $variante, int $cantidad, string $nombre): void
    {
        if ($cantidad <= 0) {
            return;
        }

        if ((int) $variante->stock < $cantidad) {
            throw new CheckoutException(
                "Stock insuficiente para \"{$nombre}\" talla {$variante->talla}. Disponible: ".max(0, (int) $variante->stock).'.'
            );
        }

        $variante->decrement('stock', $cantidad);
    }

    /**
     * Reserva (descuenta) el stock de todos los items de un pedido.
     */
    public function reservarPedido(Pedido $pedido): void
    {
        if ($pedido->stock_descontado) {
            return;
        }

        $pedido->loadMissing('items.producto');

        foreach ($pedido->items as $item) {
            $variante = $this->buscarVariante($item->producto_id, $item->talla);
            if ($variante) {
                $this->descontar($variante, (int) $item->cantidad, $this->nombre($item->producto));
            }
        }

        $pedido->forceFill(['stock_descontado' => true])->save();
    }

    /**
     * Repone el stock reservado por un pedido (p. ej. al cancelarlo).
     */
    public function liberarPedido(Pedido $pedido): void
    {
        if (! $pedido->stock_descontado) {
            return;
        }

        $pedido->loadMissing('items');

        foreach ($pedido->items as $item) {
            $variante = $this->buscarVariante($item->producto_id, $item->talla);
            if ($variante && (int) $item->cantidad > 0) {
                $variante->increment('stock', (int) $item->cantidad);
            }
        }

        $pedido->forceFill(['stock_descontado' => false])->save();
    }

    /**
     * Ajusta el stock cuando el admin edita un item de un pedido con stock
     * reservado. $delta > 0 descuenta, $delta < 0 repone.
     */
    public function ajustarItem(Pedido $pedido, int $productoId, ?string $talla, int $delta): void
    {
        if (! $pedido->stock_descontado || $delta === 0) {
            return;
        }

        $variante = $this->buscarVariante($productoId, $talla);
        if (! $variante) {
            return;
        }

        if ($delta > 0) {
            $producto = Producto::find($productoId);
            $this->descontar($variante, $delta, $producto ? $this->nombre($producto) : 'Producto');
        } else {
            $variante->increment('stock', -$delta);
        }
    }

    protected function buscarVariante(int $productoId, ?string $talla): ?ProductoVariante
    {
        if ($talla === null || trim($talla) === '') {
            return null;
        }

        return ProductoVariante::where('producto_id', $productoId)
            ->where('talla', trim($talla))
            ->lockForUpdate()
            ->first();
    }

    protected function nombre(?Producto $producto): string
    {
        if (! $producto) {
            return 'Producto';
        }

        return (string) ($producto->nombre ?: $producto->referencia);
    }
}
