<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'sku',
        'unidad',
        'stock_actual',
        'stock_minimo',
        'costo_unitario',
        'proveedor',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'costo_unitario' => 'decimal:2',
    ];
}
