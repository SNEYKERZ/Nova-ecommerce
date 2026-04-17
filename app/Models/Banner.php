<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'titulo',
        'descripcion',
        'imagen',
        'imagen_mobile',
        'url_destino',
        'plantilla_id',
        'orden',
        'esta_activo',
        'fecha_inicio',
        'fecha_fin'
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function plantilla()
    {
        return $this->belongsTo(Plantilla::class);
    }

    /**
     * Obtener banners activos para la fecha actual
     */
    public static function getActivos()
    {
        $ahora = now();
        
        return static::where('esta_activo', true)
            ->where(function ($query) use ($ahora) {
                $query->whereNull('fecha_inicio')
                    ->orWhere('fecha_inicio', '<=', $ahora);
            })
            ->where(function ($query) use ($ahora) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $ahora);
            })
            ->orderBy('orden')
            ->get();
    }
}