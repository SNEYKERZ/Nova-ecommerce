<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'categoria' => fake()->unique()->word(),
            'tipoDePrenda' => 'ropa',
        ];
    }
}
