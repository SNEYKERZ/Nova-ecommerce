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
        'bg_color',
        'navbar_color',
        'footer_color',
        'navbar_text_color',
        'footer_text_color',
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

    public function galerias(): HasMany
    {
        return $this->hasMany(Gallery::class);
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

    /**
     * Obtiene el color de texto recomendado para el navbar basado en contraste.
     * @return string Color hex (#ffffff o #111111)
     */
    public function getNavbarTextColor(): string
    {
        return app('App\Services\StylingService')::getTextColorHex($this->navbar_color ?? '#fff');
    }

    /**
     * Obtiene el color de texto recomendado para el footer basado en contraste.
     * @return string Color hex (#ffffff o #111111)
     */
    public function getFooterTextColor(): string
    {
        return app('App\Services\StylingService')::getTextColorHex($this->footer_color ?? '#fff');
    }

    /**
     * Obtiene todos los colores normalizados de la tienda.
     * @return array Array con todos los colores normalizados
     */
    public function getNormalizedColors(): array
    {
        $service = app('App\Services\StylingService');
        $defaults = $service::getDefaultColors();

        $bgColor = $service::validateColor($this->bg_color, $defaults['bg_color']);
        $navbarColor = $service::validateColor($this->navbar_color, $defaults['navbar_color']);
        $footerColor = $service::validateColor($this->footer_color, $defaults['footer_color']);

        // Si existen colores de texto explícitos, usar esos; si no, calcular automáticamente
        $navbarTextColor = $this->navbar_text_color
            ? $service::validateColor($this->navbar_text_color, '#ffffff')
            : $service::getTextColorHex($navbarColor);

        $footerTextColor = $this->footer_text_color
            ? $service::validateColor($this->footer_text_color, '#ffffff')
            : $service::getTextColorHex($footerColor);

        return [
            'bg_color' => $bgColor,
            'navbar_color' => $navbarColor,
            'footer_color' => $footerColor,
            'navbar_text_color' => $navbarTextColor,
            'footer_text_color' => $footerTextColor,
        ];
    }
}
