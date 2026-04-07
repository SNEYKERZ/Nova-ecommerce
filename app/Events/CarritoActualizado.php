<?php

namespace App\Events;

use App\Models\Carrito;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarritoActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $carrito;

    public function __construct(Carrito $carrito)
    {
        $this->carrito = $carrito;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('carrito'),
        ];
    }

    public function broadcastWith(): array
    {
        $this->carrito->load(['items.producto.categoria']);

        return [
            'carrito' => [
                'id' => $this->carrito->id,
                'items' => $this->carrito->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'producto_id' => $item->producto_id,
                        'referencia' => $item->producto->referencia,
                        'precio' => $item->producto->precio,
                        'cantidad' => $item->cantidad,
                        'talla' => $item->talla,
                        'subtotal' => $item->subtotal
                    ];
                }),
                'cantidad_total' => $this->carrito->cantidad_total,
                'total' => $this->carrito->total
            ]
        ];
    }
}