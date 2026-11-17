<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->company();

        return [
            'slug' => Str::slug($nombre).'-'.Str::lower(Str::random(4)),
            'nombre' => $nombre,
            'dominio' => null,
            'email' => fake()->companyEmail(),
            'telefono' => '3001234567',
            'activo' => true,
        ];
    }
}
