<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'nombre',
        'dominio',
        'logo_path',
        'email',
        'telefono',
        'descripcion',
        'configuracion',
        'activo',
    ];

    protected $casts = [
        'configuracion' => 'array',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    public function slides(): HasMany
    {
        return $this->hasMany(Slide::class);
    }

    public function noticias(): HasMany
    {
        return $this->hasMany(Noticia::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Accessors
    public function getDominioActivoAttribute(): ?string
    {
        return $this->dominio ?? "{$this->slug}.vendex.app";
    }

    // Helper para verificar si un dominio pertenece a esta tienda
    public function matchesDomain(string $host): bool
    {
        // Por subdomain: tienda1.vendex.app
        if ($this->slug && str_ends_with($host, ".vendex.app")) {
            $subdomain = str_replace('.vendex.app', '', $host);
            return $this->slug === $subdomain;
        }

        // Por dominio personalizado
        return $this->dominio && $this->dominio === $host;
    }
}