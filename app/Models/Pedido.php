<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'cliente_id',
        'total',
        'estado',
        'direccion_envio'
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function items()
    {
        return $this->hasMany(PedidoItem::class);
    }
}