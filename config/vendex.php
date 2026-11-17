<?php

/**
 * Vendex Multi-Tenant Configuration
 *
 * Opciones de configuración para el sistema multi-tenant de Vendex
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Dominio base de Vendex
    |--------------------------------------------------------------------------
    |
    | El dominio base donde funcionarán las tiendas de tus clientes.
    | Ejemplo: vendex.app, tu-dominio.com
    |
    */
    'domain' => env('VENDEX_DOMAIN', 'vendex.app'),

    /*
    |--------------------------------------------------------------------------
    | Tienda por defecto en localhost (sólo desarrollo)
    |--------------------------------------------------------------------------
    |
    | En localhost/127.0.0.1 se usa esta tienda (o ?store=slug). La resolución
    | por localhost sólo está habilitada cuando APP_ENV es local o testing, para
    | que en producción un header Host falso no active el modo desarrollo.
    |
    */
    'local_store_slug' => env('LOCAL_STORE_SLUG', 'demo'),

    /*
    |--------------------------------------------------------------------------
    | Rutas que no requieren tenant
    |--------------------------------------------------------------------------
    |
    | Rutas del sistema que no necesitan resolución de tenant.
    | Incluye rutas de login/admin del super admin.
    |
    */
    'except_routes' => [
        'admin/login',
        'admin/logout',
        'api/auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware de resolución de tenant
    |--------------------------------------------------------------------------
    |
    | Define el orden de los middleware para la resolución de tenant.
    |
    */
    'middleware' => [
        'web',
        'tenant', // ResolveTenant
    ],
];
