<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'imagen',
        'imagen_mobile',
        'boton_texto',
        'boton_url',
        'estilo',
        'orden',
        'esta_activo'
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
    ];

    /**
     * Obtener slides activos ordenados
     */
    public static function getActivos()
    {
        return static::where('esta_activo', true)
            ->orderBy('orden')
            ->get();
    }
}