<?php

/**
 * Configuración centralizada de estilos y colores del sistema Vendex.
 * Define valores por defecto, límites y configuraciones visuales globales.
 */

return [
    'defaults' => [
        'bg_color' => '#ffffff',
        'navbar_color' => '#fff',
        'footer_color' => '#fff',
        'text_dark' => '#111111',
        'text_light' => '#ffffff',
    ],

    'contrast' => [
        // Umbral de luminancia para determinar si usar texto claro u oscuro (0-1)
        // Valores más altos = más sensible a detectar fondo oscuro
        'threshold' => 0.5,

        // Requerimientos WCAG
        'wcag_aa' => 4.5,      // Ratio mínimo para cumplir AA (texto normal)
        'wcag_aaa' => 7.0,     // Ratio mínimo para cumplir AAA (texto normal)
    ],

    'uploads' => [
        'max_size' => 10240,    // KB
        'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'path' => 'public',     // Disco de almacenamiento Laravel
        'directories' => [
            'store' => 'store',                 // Logos y assets de tienda
            'productos' => 'productos',         // Imágenes de productos
            'banners' => 'banners',            // Banners de catálogo
            'bloques' => 'bloques',            // Imágenes de bloques home
        ],
    ],

    'validation' => [
        'color_regex' => '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        'color_message' => 'El color debe ser un valor hex válido (ej: #ffffff)',
        'url_regex' => '/^(https?:\/\/.+|\/[a-z0-9\/\-_]+|#[a-z0-9]+)$/i',
        'url_message' => 'La URL debe ser válida',
    ],

    // Componentes y sus opciones de estilo
    'components' => [
        'navbar' => [
            'height' => '56px',
            'sticky' => true,
        ],
        'footer' => [
            'background_uses_color' => true,
            'text_uses_auto_contrast' => true,
        ],
        'cards' => [
            'border_radius' => '2px',
            'hover_scale' => 1.03,
        ],
        'buttons' => [
            'border_radius' => '2px',
            'variants' => ['main', 'outline', 'soft', 'ghost'],
        ],
    ],
];
