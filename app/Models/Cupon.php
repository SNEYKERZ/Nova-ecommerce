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

    /**
     * Verificar si el cupón es válido para un subtotal dado
     */
    public function esValido(float $subtotal): bool
    {
        if (!$this->esta_activo) return false;

        if ($this->fecha_expiracion && now()->gt($this->fecha_expiracion)) return false;

        if ($this->max_usos && $this->usos_actuales >= $this->max_usos) return false;

        if ($this->monto_minimo && $subtotal < $this->monto_minimo) return false;

        return true;
    }

    /**
     * Calcular el descuento aplicable para un subtotal dado
     */
    public function calcularDescuento(float $subtotal): float
    {
        if ($this->tipo === 'porcentaje') {
            return min($subtotal * $this->valor_descuento / 100, $subtotal);
        }

        // Fijo: no puede superar el subtotal
        return min($this->valor_descuento, $subtotal);
    }
}
