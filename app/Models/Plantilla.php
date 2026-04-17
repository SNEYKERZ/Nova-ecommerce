<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plantilla extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'es_principal',
        'esta_activa',
        'configuracion'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_principal' => 'boolean',
        'esta_activa' => 'boolean',
    ];

    public function banners()
    {
        return $this->hasMany(Banner::class)->orderBy('orden');
    }

    /**
     * Obtener la plantilla activa para la fecha actual
     */
    public static function getActiva()
    {
        $ahora = now();
        
        return static::where('esta_activa', true)
            ->where(function ($query) use ($ahora) {
                $query->whereNull('fecha_inicio')
                    ->orWhere('fecha_inicio', '<=', $ahora);
            })
            ->where(function ($query) use ($ahora) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $ahora);
            })
            ->orderBy('es_principal', 'desc')
            ->first();
    }

    /**
     * Aplicar configuración de la plantilla a la vista
     */
    public function getConfiguracionVisual()
    {
        $config = $this->configuracion ?? [];
        return array_merge([
            'color_primary' => '#0c6e09',
            'color_secondary' => '#343a40',
            'banner_home' => null,
            'css_custom' => '',
            'Efecto_animacion' => null,
        ], $config);
    }
}