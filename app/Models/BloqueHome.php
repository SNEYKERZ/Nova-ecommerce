<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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
        return $this->hasMany(BloqueHomeImagen::class)->orderBy('orden');
    }

    /**
     * Scope para bloques activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('posicion');
    }

    /**
     * Obtener bloque por posición SIN global scope (para frontend público)
     */
    public static function getPorPosicion(int $posicion)
    {
        // Sin global scope para que funcione en el homepage público
        $bloque = static::withoutGlobalScopes()
            ->where('posicion', $posicion)
            ->where('activo', true)
            ->first();
        
        // Cargar imágenes también sin global scope
        if ($bloque) {
            $bloque->setRelation('imagenes', \App\Models\BloqueHomeImagen::withoutGlobalScopes()
                ->where('bloque_home_id', $bloque->id)
                ->orderBy('orden')
                ->get());
        }
        
        return $bloque;
    }

    /**
     * Obtener todos los bloques (sin scope) para admin
     */
    public static function getAllPorPosicion(int $posicion)
    {
        $bloque = static::withoutGlobalScopes()
            ->where('posicion', $posicion)
            ->first();

        if ($bloque) {
            $bloque->setRelation('imagenes', \App\Models\BloqueHomeImagen::withoutGlobalScopes()
                ->where('bloque_home_id', $bloque->id)
                ->orderBy('orden')
                ->get());
        }

        return $bloque;
    }
}