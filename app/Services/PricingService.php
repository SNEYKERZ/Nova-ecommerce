<?php

namespace App\Services;

use App\Models\Oferta;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Support\Collection;

/**
 * Única fuente de verdad para precios en servidor.
 *
 * - Precio con oferta: la mejor (más baja) entre las ofertas activas del
 *   producto o de su categoría. Una oferta nunca sube el precio.
 * - Precio unitario de venta: precio con oferta + precio_adicional de la variante.
 *
 * Las ofertas activas se cargan una sola vez por request y tienda (evita N+1).
 */
class PricingService
{
    /** @var array<int|string, Collection> */
    protected array $ofertasPorTienda = [];

    public function __construct(protected TenantManager $tenantManager) {}

    public function ofertasActivas(): Collection
    {
        $key = $this->tenantManager->getStore()?->id ?? 'global';

        return $this->ofertasPorTienda[$key] ??= Oferta::activas()->get();
    }

    public function flush(): void
    {
        $this->ofertasPorTienda = [];
    }

    public function precioConOferta(Producto $producto): float
    {
        $base = (float) $producto->precio;
        $mejor = $base;

        foreach ($this->ofertasActivas() as $oferta) {
            $aplica = $oferta->producto_id
                ? (int) $oferta->producto_id === (int) $producto->id
                : ($oferta->categoria_id && (int) $oferta->categoria_id === (int) $producto->categoria_id);

            if (! $aplica) {
                continue;
            }

            $precio = $this->aplicarOferta($oferta, $base);
            if ($precio < $mejor) {
                $mejor = $precio;
            }
        }

        return round(max(0, $mejor), 2);
    }

    public function precioUnitario(Producto $producto, ?ProductoVariante $variante = null): float
    {
        $adicional = $variante ? (float) $variante->precio_adicional : 0.0;

        return round(max(0, $this->precioConOferta($producto) + $adicional), 2);
    }

    protected function aplicarOferta(Oferta $oferta, float $base): float
    {
        if ((float) $oferta->precio_oferta > 0) {
            return min($base, (float) $oferta->precio_oferta);
        }

        if ((float) $oferta->descuento_porcentaje > 0) {
            $pct = min(100, (float) $oferta->descuento_porcentaje);

            return $base * (1 - $pct / 100);
        }

        if ((float) $oferta->descuento_fijo > 0) {
            return max(0, $base - (float) $oferta->descuento_fijo);
        }

        return $base;
    }
}
