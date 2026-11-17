<?php

namespace Database\Factories;

use App\Models\Oferta;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Oferta>
 */
class OfertaFactory extends Factory
{
    protected $model = Oferta::class;

    public function definition(): array
    {
        return [
            'titulo' => 'Promo',
            'producto_id' => null,
            'categoria_id' => null,
            'descuento_porcentaje' => 20,
            'descuento_fijo' => null,
            'precio_oferta' => null,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDay(),
            'esta_activa' => true,
            'store_id' => fn (array $attributes) => isset($attributes['producto_id'])
                ? Producto::withoutGlobalScopes()->find($attributes['producto_id'])?->store_id
                : null,
        ];
    }
}
