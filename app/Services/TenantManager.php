<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Http\Request;

/**
 * TenantManager - Servicio central para gestión de multi-tenant
 * 
 * Responsabilidades:
 * - Detectar el tenant actual desde el request (subdomain o dominio)
 * - Proporcionar acceso al store actual
 * - Manejar la lógica de super admin vs admin de store
 */
class TenantManager
{
    protected ?Store $currentStore = null;
    protected bool $isSuperAdmin = false;

    /**
     * Obtener el dominio desde config o usar valor por defecto
     */
    protected function getDomainSuffix(): string
    {
        return config('vendex.domain', 'vendex.app');
    }

    /**
     * Obtener el store actual (singleton en el request)
     */
    public function getStore(): ?Store
    {
        return $this->currentStore;
    }

    /**
     * Establecer el store actual (usado por el middleware)
     */
    public function setStore(?Store $store): self
    {
        $this->currentStore = $store;
        return $this;
    }

    /**
     * Verificar si hay un store activo
     */
    public function hasStore(): bool
    {
        return $this->currentStore !== null;
    }

    /**
     * Verificar si es super admin (acceso a todos los stores)
     */
    public function isSuperAdmin(): bool
    {
        return $this->isSuperAdmin;
    }

    /**
     * Establecer modo super admin
     */
    public function setSuperAdmin(bool $value = true): self
    {
        $this->isSuperAdmin = $value;
        return $this;
    }

    /**
     * Resolver el tenant desde el request HTTP
     * Soporta: subdomains (tienda1.vendex.app) y dominios personalizados
     */
    public function resolveFromRequest(Request $request): ?Store
    {
        $host = $request->getHost();
        $domainSuffix = $this->getDomainSuffix();
        
        // 1. Buscar por subdomain: tienda1.vendex.app
        $subdomain = $this->extractSubdomain($host, $domainSuffix);
        if ($subdomain) {
            return Store::where('slug', $subdomain)->where('activo', true)->first();
        }

        // 2. Buscar por dominio personalizado: mitienda.com
        return Store::where('dominio', $host)->where('activo', true)->first();
    }

    /**
     * Extraer el subdomain del host
     * Ej: tienda1.vendex.app -> tienda1
     */
    protected function extractSubdomain(string $host, string $domainSuffix): ?string
    {
        // Si el host termina en el dominio configurado, extraer el subdomain
        if (str_ends_with($host, '.' . $domainSuffix)) {
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                return $parts[0]; // Primera parte es el subdomain
            }
        }

        // Excepción: localhost para desarrollo
        if ($host === 'localhost' || str_starts_with($host, 'localhost:')) {
            // En desarrollo, usar el store demo si existe
            return 'demo';
        }

        return null;
    }

    /**
     * Obtener el prefijo de dominio para generar URLs
     */
    public function getDomainPrefix(): string
    {
        return $this->getDomainSuffix();
    }

    /**
     * Setear el suffix de dominio (para testing o configuración)
     */
    public function setDomainSuffix(string $suffix): self
    {
        // Solo para testing
        return $this;
    }

    /**
     * Limpiar el tenant actual (para logout o cambio de store)
     */
    public function clear(): self
    {
        $this->currentStore = null;
        $this->isSuperAdmin = false;
        return $this;
    }

    /**
     * Obtener la URL base del tenant actual
     */
    public function getBaseUrl(): string
    {
        if (!$this->currentStore) {
            return config('app.url');
        }

        if ($this->currentStore->dominio) {
            return 'https://' . $this->currentStore->dominio;
        }

        return 'https://' . $this->currentStore->slug . '.' . $this->getDomainSuffix();
    }
}