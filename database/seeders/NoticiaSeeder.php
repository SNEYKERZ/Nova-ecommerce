<?php

namespace Database\Seeders;

use App\Models\Noticia;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NoticiaSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command->warn('No hay tiendas. Ejecuta el StoreSeeder primero.');
            return;
        }

        Noticia::ignoreTenantScope();

        $promos = [
            'demo' => 'Nueva coleccion urbana 2026, 20% off en segunda prenda, Envio gratis desde $180.000',
            'urbanstyle' => 'Estreno temporada Otono 2026, 15% OFF en tu primera compra, Envio gratis desde $200.000',
        ];

        foreach ($stores as $store) {
            $mensaje = $promos[$store->slug] ?? 'Bienvenido a ' . $store->nombre;

            Noticia::updateOrCreate(
                ['store_id' => $store->id],
                [
                    'campos_adicionales' => $mensaje,
                    'store_id' => $store->id,
                ]
            );
        }

        Noticia::restoreTenantScope();
    }
}
