<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoImagen extends Model
{
    use HasFactory;

    protected $fillable = ['producto_id', 'imagen', 'orden', 'es_principal'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Obtener la imagen principal de un producto
     */
    public static function getPrincipal($productoId)
    {
        return static::where('producto_id', $productoId)
            ->where('es_principal', true)
            ->first();
    }

    /**
     * Obtener todas las imágenes de un producto ordenadas
     */
    public static function getGaleria($productoId)
    {
        return static::where('producto_id', $productoId)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }
}