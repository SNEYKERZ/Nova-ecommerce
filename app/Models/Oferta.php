<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oferta extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'titulo',
        'descripcion',
        'producto_id',
        'categoria_id',
        'descuento_porcentaje',
        'descuento_fijo',
        'precio_oferta',
        'fecha_inicio',
        'fecha_fin',
        'esta_activa',
    ];

    protected $casts = [
        'descuento_porcentaje' => 'decimal:2',
        'descuento_fijo' => 'decimal:2',
        'precio_oferta' => 'decimal:2',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'esta_activa' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public static function getOfertaActiva($productoId = null)
    {
        $ahora = now();

        return static::where('esta_activa', true)
            ->where('fecha_inicio', '<=', $ahora)
            ->where('fecha_fin', '>=', $ahora)
            ->when($productoId, fn ($q) => $q->where('producto_id', $productoId))
            ->first();
    }

    public static function getOfertasActivas()
    {
        $ahora = now();

        return static::where('esta_activa', true)
            ->where('fecha_inicio', '<=', $ahora)
            ->where('fecha_fin', '>=', $ahora)
            ->get();
    }
}
