<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloqueHome extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'tipo',           // 'banner' o 'texto'
        'posicion',       // 1 o 2
        'titulo',         // para tipo texto
        'contenido',      // para tipo texto
        'tamano_texto',   // 'normal' o 'grande'
        'activo',        // boolean
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Imágenes del bloque (cuando tipo = banner)
     */
    public function imagenes(): HasMany
    {
        // El bloque padre ya está filtrado por tienda; se omite el scope en las
        // imágenes para no perder registros legacy con store_id NULL.
        return $this->hasMany(BloqueHomeImagen::class)->withoutGlobalScope('tenant')->orderBy('orden');
    }

    /**
     * Scope para bloques activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('posicion');
    }

    /**
     * Obtener bloque activo por posición de la tienda actual (frontend público).
     *
     * Usa el global scope de HasTenant: antes se usaba withoutGlobalScopes(),
     * lo que mostraba (y permitía editar desde el admin) el bloque de OTRA tienda.
     */
    public static function getPorPosicion(int $posicion)
    {
        return static::query()
            ->where('posicion', $posicion)
            ->where('activo', true)
            ->with('imagenes')
            ->first();
    }

    /**
     * Obtener bloque por posición (activo o no) de la tienda actual, para admin.
     */
    public static function getAllPorPosicion(int $posicion)
    {
        return static::query()
            ->where('posicion', $posicion)
            ->with('imagenes')
            ->first();
    }
}
