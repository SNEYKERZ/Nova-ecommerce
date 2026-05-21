<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        // Sin global scopes para el seeder
        Store::withoutGlobalScopes();

        // ─── Tienda 1: Demo ───────────────────────────────
        Store::updateOrCreate(
            ['slug' => 'demo'],
            [
                'nombre' => 'WebCaps Demo',
                'dominio' => null,
                'email' => 'admin@webcaps.demo',
                'telefono' => '+51 999 888 777',
                'descripcion' => 'Tienda demo de WebCaps — gorras y accesorios urbanos',
                'bg_color' => '#ffffff',
                'navbar_color' => '#ffffff',
                'footer_color' => '#e8e6e3',
                'navbar_text_color' => '#111111',
                'footer_text_color' => '#111111',
                'configuracion' => [
                    'social' => [
                        'facebook' => 'https://facebook.com/webcaps',
                        'instagram' => 'https://instagram.com/webcaps',
                        'tiktok' => 'https://tiktok.com/@webcaps',
                    ],
                    'empresas_url' => 'https://webcaps.com/empresas',
                ],
                'activo' => true,
            ]
        );

        // ─── Tienda 2: Urban Style ─────────────────────────
        Store::updateOrCreate(
            ['slug' => 'urbanstyle'],
            [
                'nombre' => 'Urban Style Store',
                'dominio' => null,
                'email' => 'admin@urbanstyle.store',
                'telefono' => '+51 999 111 222',
                'descripcion' => 'Moda urbana y estilo de vida — ropa, accesorios y más',
                'bg_color' => '#f8f6f3',
                'navbar_color' => '#1a1a2e',
                'footer_color' => '#1a1a2e',
                'navbar_text_color' => '#ffffff',
                'footer_text_color' => '#ffffff',
                'configuracion' => [
                    'social' => [
                        'facebook' => 'https://facebook.com/urbanstyle',
                        'instagram' => 'https://instagram.com/urbanstyle',
                        'tiktok' => 'https://tiktok.com/@urbanstyle',
                    ],
                    'empresas_url' => 'https://urbanstyle.store/corporate',
                ],
                'activo' => true,
            ]
        );
    }
}
