<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'referencia' => 'REF-'.fake()->unique()->numerify('#####'),
            'nombre' => fake()->words(2, true),
            'precio' => 100000,
            'estado' => 'DISPONIBLE',
            'store_id' => fn (array $attributes) => isset($attributes['categoria_id'])
                ? Categoria::withoutGlobalScopes()->find($attributes['categoria_id'])?->store_id
                : null,
        ];
    }
}
