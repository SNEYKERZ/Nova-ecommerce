<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Talla extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'tipo', 'orden'];

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class, 'talla', 'nombre');
    }
}