<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'sesion_id', 'producto_id'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class Resena extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'user_id',
        'nombre',
        'email',
        'calificacion',
        'comentario',
        'aprobada'
    ];

    protected $casts = [
        'aprobada' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class HistorialPrecio extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'precio_anterior',
        'precio_nuevo',
        'motivo'
    ];

    protected $casts = [
        'precio_anterior' => 'decimal:2',
        'precio_nuevo' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}

class Oferta extends Model
{
    use HasFactory;

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
        'esta_activa'
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

    /**
     * Obtener oferta activa para un producto específico
     */
    public static function getOfertaActiva($productoId = null)
    {
        $ahora = now();
        
        return static::where('esta_activa', true)
            ->where('fecha_inicio', '<=', $ahora)
            ->where('fecha_fin', '>=', $ahora)
            ->when($productoId, fn($q) => $q->where('producto_id', $productoId))
            ->first();
    }

    /**
     * Obtener todas las ofertas activas
     */
    public static function getOfertasActivas()
    {
        $ahora = now();
        
        return static::where('esta_activa', true)
            ->where('fecha_inicio', '<=', $ahora)
            ->where('fecha_fin', '>=', $ahora)
            ->get();
    }
}