<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogBanner extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'posicion',
        'nombre',
        'identificador',
        'imagen',
        'url_destino',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Valida que una URL de destino sea válida
     * Acepta: URLs absolutas (http/https), rutas relativas, hashes
     */
    public static function isValidUrl(?string $url): bool
    {
        if (empty($url)) {
            return true; // URL opcional
        }

        return (bool) preg_match('/^(https?:\/\/.+|\/[a-z0-9\/_-]*|#[a-z0-9-]*)$/i', $url);
    }

    /**
     * Scope para obtener banners activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para obtener banners por posición
     */
    public function scopeEnPosicion($query, int $posicion)
    {
        return $query->where('posicion', $posicion);
    }
}
