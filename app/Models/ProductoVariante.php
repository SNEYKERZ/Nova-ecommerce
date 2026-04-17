<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'producto_id',
        'sku',
        'talla',
        'color',
        'stock',
        'precio_adicional',
        'esta_activa'
    ];

    protected $casts = [
        'precio_adicional' => 'decimal:2',
        'esta_activa' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Obtener precio final (precio base + precio adicional de variante)
     */
    public function getPrecioFinalAttribute()
    {
        return ($this->producto->precio ?? 0) + $this->precio_adicional;
    }

    /**
     * Obtener stock disponible
     */
    public function getStockDisponibleAttribute()
    {
        return $this->stock > 0 ? $this->stock : 0;
    }
}