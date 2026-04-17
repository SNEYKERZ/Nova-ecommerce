<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'referencia',
        'precio',
        'foto',
        'tallas',
        'categoria_id',
        'estado',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'destacado' => 'boolean',
        'nuevo' => 'boolean',
    ];

    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class)->orderBy('orden');
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class);
    }

    public function ofertas()
    {
        return $this->hasMany(Oferta::class);
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class)->where('aprobada', true);
    }

    public function historialPrecios()
    {
        return $this->hasMany(HistorialPrecio::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Scopes
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'DISPONIBLE');
    }

    public function scopeDestacados($query)
    {
        return $query->where('destacado', true)->where('estado', 'DISPONIBLE');
    }

    public function scopeNuevos($query)
    {
        return $query->where('nuevo', true)->where('estado', 'DISPONIBLE');
    }

    // Accessors
    public function getTallasArrayAttribute()
    {
        if ($this->variantes->count() > 0) {
            return $this->variantes->pluck('talla')->unique()->filter()->values()->toArray();
        }
        return $this->tallas ? explode(',', $this->tallas) : [];
    }

    public function getColoresAttribute()
    {
        if ($this->variantes->count() > 0) {
            return $this->variantes->pluck('color')->unique()->filter()->values()->toArray();
        }
        return [];
    }

    // Obtener imagen principal o la primera disponible
    public function getImagenPrincipalAttribute()
    {
        $imagen = $this->imagenes()->where('es_principal', true)->first();
        if (!$imagen) {
            $imagen = $this->imagenes()->first();
        }
        return $imagen;
    }

    // Obtener galería de imágenes (max 5)
    public function getGaleriaAttribute()
    {
        return $this->imagenes()->orderBy('orden')->limit(5)->get();
    }

    // Verificar si tiene stock
    public function getTieneStockAttribute()
    {
        if ($this->variantes->count() > 0) {
            return $this->variantes()->where('stock', '>', 0)->exists();
        }
        return true; // Sin variantes, asume disponible
    }

    // Obtener precio con oferta activa
    public function getPrecioConOfertaAttribute()
    {
        $oferta = Oferta::getOfertaActiva($this->id);
        
        if ($oferta) {
            if ($oferta->precio_oferta) {
                return $oferta->precio_oferta;
            }
            if ($oferta->descuento_porcentaje) {
                return $this->precio * (1 - $oferta->descuento_porcentaje / 100);
            }
            if ($oferta->descuento_fijo) {
                return max(0, $this->precio - $oferta->descuento_fijo);
            }
        }
        
        return $this->precio;
    }

    // Calificación promedio
    public function getCalificacionPromedioAttribute()
    {
        return $this->resenas()->avg('calificacion') ?? 0;
    }

    // Total de reseñas
    public function getTotalResenasAttribute()
    {
        return $this->resenas()->count();
    }
}
