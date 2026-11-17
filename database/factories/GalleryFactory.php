<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    protected $model = Gallery::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'nombre' => fake()->unique()->words(2, true),
            'descripcion' => null,
            'orden' => 0,
            'activo' => true,
        ];
    }
}
