<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'apellidos', 'email', 'telefono', 'direccion'];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function carritos()
    {
        return $this->hasMany(Carrito::class);
    }
}