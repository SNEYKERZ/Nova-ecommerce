<?php

namespace App\Traits;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasTenant
 * 
 * Agrega automáticamente filtrado por store_id a todos los queries.
 * Uso: en modelos que tienen store_id
 */
trait HasTenant
{
    /**
     * Property para ignorar el scope temporalmente
     */
    protected static bool $ignoreTenantScope = false;

    /**
     * Boot the trait
     */
    public static function bootHasTenant(): void
    {
        // Global scope: filtrar automáticamente por el store actual
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Si hay un store activo en el contexto, filtrar por él
            // Pero primero verificar si se deshabilitó temporalmente el scope
            if (!static::shouldIgnoreTenantScope()) {
                if ($store = app('tenant.store')) {
                    $builder->where('store_id', $store->id);
                }
            }
        });

        // Al crear, asignar automáticamente el store_id del tenant actual
        static::creating(function ($model) {
            if (empty($model->store_id) && !$model->isSuperAdmin()) {
                $store = app('tenant.store');
                if ($store) {
                    $model->store_id = $store->id;
                }
            }
        });
    }

    /**
     * Relación con Store
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope sin el global scope (para admin global o seeders)
     */
    public function scopeAllStores(Builder $builder): Builder
    {
        return $builder->withoutGlobalScopes();
    }

    /**
     * Scope para un store específico
     */
    public function scopeForStore(Builder $builder, Store|int $store): Builder
    {
        $storeId = $store instanceof Store ? $store->id : $store;
        return $builder->withoutGlobalScopes()->where('store_id', $storeId);
    }

    /**
     * Ignorar el tenant scope temporalmente (útil para seeders)
     */
    public static function ignoreTenantScope(): void
    {
        static::$ignoreTenantScope = true;
    }

    /**
     * Restaurar el tenant scope
     */
    public static function restoreTenantScope(): void
    {
        static::$ignoreTenantScope = false;
    }

    /**
     * Verificar si debe ignorar el tenant scope
     */
    protected static function shouldIgnoreTenantScope(): bool
    {
        return static::$ignoreTenantScope ?? false;
    }

    /**
     * Verificar si es super admin (sin restricciones de tenant)
     */
    protected function isSuperAdmin(): bool
    {
        // El super admin puede actuar en cualquier store
        // Se verifica en el TenantManager
        $tenantManager = app(\App\Services\TenantManager::class);
        return $tenantManager->isSuperAdmin();
    }
}