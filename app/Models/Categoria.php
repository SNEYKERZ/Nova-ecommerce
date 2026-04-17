<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = ['categoria', 'tipoDePrenda'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}