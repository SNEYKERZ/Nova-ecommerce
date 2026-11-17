<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = ['cliente_id', 'sesion_id', 'estado', 'cupon_id', 'descuento_cupon'];

    // El ID de sesión nunca debe viajar al navegador (anularía HttpOnly de la cookie)
    protected $hidden = ['sesion_id'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function items()
    {
        return $this->hasMany(CarritoItem::class);
    }

    public function cupon()
    {
        return $this->belongsTo(Cupon::class);
    }

    /**
     * Subtotal (sin cupón) con precios vigentes calculados en servidor.
     */
    public function getTotalAttribute()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->subtotal;
        }

        return round($total, 2);
    }

    /**
     * Cupón aplicado sólo si sigue siendo válido para el subtotal actual.
     */
    public function cuponVigente(): ?Cupon
    {
        if (! $this->cupon_id || ! $this->cupon) {
            return null;
        }

        return $this->cupon->esValido((float) $this->total) ? $this->cupon : null;
    }

    /**
     * Descuento recalculado contra el subtotal actual (no se confía en el
     * valor guardado al aplicar el cupón: el carrito pudo cambiar después).
     */
    public function descuentoVigente(): float
    {
        $cupon = $this->cuponVigente();

        return $cupon ? $cupon->calcularDescuento((float) $this->total) : 0.0;
    }

    public function totalConDescuento(): float
    {
        return round(max(0, (float) $this->total - $this->descuentoVigente()), 2);
    }

    public function getCantidadTotalAttribute()
    {
        return $this->items->sum('cantidad');
    }

    public static function getOrCreateCarrito()
    {
        $sesionId = session()->getId();
        if (! $sesionId) {
            $sesionId = md5(microtime(true).rand(1000, 9999));
        }

        $carrito = static::where('sesion_id', $sesionId)
            ->where('estado', 'ACTIVO')
            ->first();

        if (! $carrito) {
            $carrito = static::create([
                'sesion_id' => $sesionId,
                'estado' => 'ACTIVO',
            ]);
        }

        return $carrito;
    }
}
