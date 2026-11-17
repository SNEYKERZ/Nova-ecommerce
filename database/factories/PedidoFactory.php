<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'cliente_id' => null,
            'total' => 0,
            'estado' => 'PENDIENTE',
            'origen' => 'web',
            'direccion_envio' => 'Calle 1',
            'descuento_cupon' => 0,
            'stock_descontado' => false,
        ];
    }
}
