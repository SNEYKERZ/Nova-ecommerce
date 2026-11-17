<?php

namespace App\Models;

use App\Services\PricingService;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarritoItem extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = ['carrito_id', 'producto_id', 'cantidad', 'talla'];

    public function carrito()
    {
        return $this->belongsTo(Carrito::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Precio unitario vigente (con ofertas y precio adicional de la variante),
     * calculado en servidor con la misma regla que el checkout.
     */
    public function getPrecioUnitarioAttribute(): float
    {
        if (! $this->producto) {
            return 0.0;
        }

        $variantes = $this->producto->relationLoaded('variantes')
            ? $this->producto->variantes
            : $this->producto->variantes()->get();

        $variante = $this->talla !== null
            ? $variantes->firstWhere('talla', $this->talla)
            : null;

        return app(PricingService::class)->precioUnitario($this->producto, $variante);
    }

    public function getSubtotalAttribute()
    {
        return round($this->precio_unitario * $this->cantidad, 2);
    }
}
