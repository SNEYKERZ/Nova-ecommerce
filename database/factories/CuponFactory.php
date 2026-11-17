<?php

namespace Database\Factories;

use App\Models\Cupon;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cupon>
 */
class CuponFactory extends Factory
{
    protected $model = Cupon::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'codigo' => strtoupper(fake()->unique()->bothify('CUPON##??')),
            'tipo' => 'porcentaje',
            'valor_descuento' => 10,
            'monto_minimo' => null,
            'max_usos' => null,
            'usos_actuales' => 0,
            'fecha_expiracion' => null,
            'esta_activo' => true,
        ];
    }
}
