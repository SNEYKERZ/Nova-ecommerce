<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Noticia extends Model
{
    use HasFactory;

    protected $fillable = ['campos_adicionales'];

    public static function getPromocionesActivas()
    {
        $noticia = static::first();
        if ($noticia && $noticia->campos_adicionales) {
            return explode(',', $noticia->campos_adicionales);
        }
        return [];
    }
}