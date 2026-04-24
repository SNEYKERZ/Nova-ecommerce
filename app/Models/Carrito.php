<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = ['cliente_id', 'sesion_id', 'estado'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function items()
    {
        return $this->hasMany(CarritoItem::class);
    }

    public function getTotalAttribute()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->subtotal;
        }
        return $total;
    }

    public function getCantidadTotalAttribute()
    {
        return $this->items->sum('cantidad');
    }

    public static function getOrCreateCarrito()
    {
        $sesionId = session()->getId();
        
        $carrito = static::where('sesion_id', $sesionId)
            ->where('estado', 'ACTIVO')
            ->first();

        if (!$carrito) {
            $carrito = static::create([
                'sesion_id' => $sesionId,
                'estado' => 'ACTIVO'
            ]);
        }

        return $carrito;
    }
}