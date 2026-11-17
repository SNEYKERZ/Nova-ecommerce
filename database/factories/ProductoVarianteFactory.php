<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductoVariante>
 */
class ProductoVarianteFactory extends Factory
{
    protected $model = ProductoVariante::class;

    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'sku' => null,
            'talla' => 'M',
            'color' => 'STD',
            'stock' => 10,
            'precio_adicional' => 0,
            'esta_activa' => true,
            'store_id' => fn (array $attributes) => Producto::withoutGlobalScopes()->find($attributes['producto_id'])?->store_id,
        ];
    }
}
