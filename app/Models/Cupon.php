<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cupon extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'cupones';

    protected $fillable = [
        'codigo',
        'tipo',
        'valor_descuento',
        'monto_minimo',
        'max_usos',
        'usos_actuales',
        'fecha_expiracion',
        'esta_activo',
    ];

    protected $casts = [
        'valor_descuento' => 'decimal:2',
        'monto_minimo' => 'decimal:2',
        'fecha_expiracion' => 'datetime',
        'esta_activo' => 'boolean',
        'usos_actuales' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function ($cupon) {
            if ($cupon->valor_descuento < 0) {
                $cupon->valor_descuento = 0;
            }
        });
    }

    /**
     * Verificar si el cupón es válido para un subtotal dado
     */
    public function esValido(float $subtotal): bool
    {
        if (! $this->esta_activo) {
            return false;
        }

        if ($this->estaExpirado()) {
            return false;
        }

        if ($this->max_usos && $this->usos_actuales >= $this->max_usos) {
            return false;
        }

        if ($this->monto_minimo && $subtotal < $this->monto_minimo) {
            return false;
        }

        return true;
    }

    /**
     * Una fecha sin hora (00:00:00) se interpreta como "válido todo ese día".
     */
    public function estaExpirado(): bool
    {
        if (! $this->fecha_expiracion) {
            return false;
        }

        $limite = $this->fecha_expiracion->format('H:i:s') === '00:00:00'
            ? $this->fecha_expiracion->copy()->endOfDay()
            : $this->fecha_expiracion;

        return now()->gt($limite);
    }

    /**
     * Calcular el descuento aplicable para un subtotal dado
     */
    public function calcularDescuento(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        if ($this->tipo === 'porcentaje') {
            $pct = min(100, max(0, (float) $this->valor_descuento));

            return round(min($subtotal * $pct / 100, $subtotal), 2);
        }

        // Fijo: no puede superar el subtotal
        return round(min((float) $this->valor_descuento, $subtotal), 2);
    }
}
