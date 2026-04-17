<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'nombre',
        'sku',
        'unidad',
        'tipo_registro',
        'unidades_por_paquete',
        'cantidad_compra',
        'costo_total_compra',
        'stock_actual',
        'stock_minimo',
        'costo_unitario',
        'proveedor',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'unidades_por_paquete' => 'integer',
        'cantidad_compra' => 'integer',
        'costo_total_compra' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
    ];
}
