<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogBanner extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'posicion',
        'nombre',
        'identificador',
        'imagen',
        'url_destino',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
