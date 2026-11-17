<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\Oferta;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create(['slug' => 'tienda']);
        $this->categoria = Categoria::factory()->create(['store_id' => $this->store->id]);
    }

    private function producto(array $attrs = [], ?int $stockM = null): Producto
    {
        $producto = Producto::factory()->create(array_merge([
            'store_id' => $this->store->id,
            'categoria_id' => $this->categoria->id,
            'precio' => 100000,
        ], $attrs));

        if ($stockM !== null) {
            ProductoVariante::factory()->create([
                'store_id' => $this->store->id,
                'producto_id' => $producto->id,
                'talla' => 'M',
                'stock' => $stockM,
            ]);
        }

        return $producto;
    }

    private function datosCliente(array $items): array
    {
        return [
            'nombre' => 'Ana',
            'apellidos' => 'Pérez',
            'email' => 'ana@example.com',
            'telefono' => '3000000000',
            'direccion' => 'Calle 1 # 2-3',
            'items' => $items,
        ];
    }

    private function varianteM(Producto $producto): ProductoVariante
    {
        return ProductoVariante::withoutGlobalScopes()->where('producto_id', $producto->id)->where('talla', 'M')->firstOrFail();
    }

    public function test_order_total_is_computed_on_server_ignoring_client_prices(): void
    {
        $p1 = $this->producto(['precio' => 100000], stockM: 10);
        $p2 = $this->producto(['precio' => 25000]); // sin variantes => sin control de stock

        $response = $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $p1->id, 'cantidad' => 2, 'talla' => 'M', 'precio' => 1, 'subtotal' => 1],
            ['producto_id' => $p2->id, 'cantidad' => 1, 'precio' => 1],
        ]) + ['total' => 1]);

        $response->assertCreated()->assertJsonPath('success', true);

        $pedido = Pedido::withoutGlobalScopes()->findOrFail($response->json('data.id'));
        $this->assertSame('225000.00', (string) $pedido->total);
        $this->assertSame($this->store->id, (int) $pedido->store_id);
        $this->assertTrue($pedido->stock_descontado);
        $this->assertSame('100000.00', (string) $pedido->items()->withoutGlobalScopes()->where('producto_id', $p1->id)->value('precio_unitario'));

        // Stock descontado de la variante
        $this->assertSame(8, (int) $this->varianteM($p1)->stock);
    }

    public function test_active_offers_are_applied_server_side(): void
    {
        $producto = $this->producto(['precio' => 100000]);
        Oferta::factory()->create([
            'store_id' => $this->store->id,
            'producto_id' => $producto->id,
            'descuento_porcentaje' => 20,
        ]);
        // Oferta por categoría (antes nunca se aplicaba): la mejor gana
        Oferta::factory()->create([
            'store_id' => $this->store->id,
            'categoria_id' => $this->categoria->id,
            'descuento_porcentaje' => 30,
        ]);
        // Oferta vencida: no aplica
        Oferta::factory()->create([
            'store_id' => $this->store->id,
            'producto_id' => $producto->id,
            'precio_oferta' => 1000,
            'fecha_inicio' => now()->subWeek(),
            'fecha_fin' => now()->subDay(),
        ]);

        $response = $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 1],
        ]))->assertCreated();

        $this->assertSame('70000.00', (string) Pedido::withoutGlobalScopes()->find($response->json('data.id'))->total);
    }

    public function test_insufficient_stock_is_rejected_and_nothing_is_persisted(): void
    {
        $producto = $this->producto(stockM: 1);

        $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 2, 'talla' => 'M'],
        ]))->assertStatus(422)->assertJsonPath('success', false);

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertSame(1, (int) $this->varianteM($producto)->stock);
    }

    public function test_repeated_lines_are_aggregated_for_stock_check(): void
    {
        $producto = $this->producto(stockM: 3);

        $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 2, 'talla' => 'M'],
            ['producto_id' => $producto->id, 'cantidad' => 2, 'talla' => 'M'],
        ]))->assertStatus(422);

        $this->assertSame(3, (int) $this->varianteM($producto)->stock);
    }

    public function test_size_is_required_and_must_exist_for_products_with_variants(): void
    {
        $producto = $this->producto(stockM: 5);

        $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 1],
        ]))->assertStatus(422);

        $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 1, 'talla' => 'XXL'],
        ]))->assertStatus(422);

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_unavailable_or_foreign_products_cannot_be_ordered(): void
    {
        $noDisponible = $this->producto(['estado' => 'NO_DISPONIBLE']);

        $otraTienda = Store::factory()->create();
        $categoriaOtra = Categoria::factory()->create(['store_id' => $otraTienda->id]);
        $ajeno = Producto::factory()->create(['store_id' => $otraTienda->id, 'categoria_id' => $categoriaOtra->id]);

        $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $noDisponible->id, 'cantidad' => 1],
        ]))->assertStatus(422);

        $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $ajeno->id, 'cantidad' => 1],
        ]))->assertStatus(422)->assertJsonValidationErrors('items.0.producto_id');

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_same_customer_email_can_order_in_two_stores(): void
    {
        $producto = $this->producto();
        $otraTienda = Store::factory()->create(['slug' => 'otra']);
        $categoriaOtra = Categoria::factory()->create(['store_id' => $otraTienda->id]);
        $productoOtra = Producto::factory()->create(['store_id' => $otraTienda->id, 'categoria_id' => $categoriaOtra->id]);

        $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 1],
        ]))->assertCreated();

        // Antes: clientes.email era único global => error 500 en la segunda tienda
        $this->postJson($this->storeUrl($otraTienda, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $productoOtra->id, 'cantidad' => 1],
        ]))->assertCreated();

        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_admin_status_changes_do_not_double_count_stock(): void
    {
        $producto = $this->producto(stockM: 10);
        $admin = $this->adminOf($this->store);

        $pedidoId = $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 3, 'talla' => 'M'],
        ]))->assertCreated()->json('data.id');

        $this->assertSame(7, (int) $this->varianteM($producto)->stock);

        $url = $this->storeUrl($this->store, "/admin/pedidos/{$pedidoId}/estado");
        $this->actingAs($admin);

        // Confirmar no vuelve a descontar (antes: doble descuento)
        $this->putJson($url, ['estado' => 'CONFIRMADO'])->assertOk();
        $this->putJson($url, ['estado' => 'CONFIRMADO'])->assertOk();
        $this->assertSame(7, (int) $this->varianteM($producto)->stock);

        // Cancelar repone una sola vez
        $this->putJson($url, ['estado' => 'CANCELADO'])->assertOk();
        $this->putJson($url, ['estado' => 'CANCELADO'])->assertOk();
        $this->assertSame(10, (int) $this->varianteM($producto)->stock);

        // Reactivar vuelve a reservar
        $this->putJson($url, ['estado' => 'PENDIENTE'])->assertOk();
        $this->assertSame(7, (int) $this->varianteM($producto)->stock);
    }

    public function test_admin_item_edits_adjust_reserved_stock(): void
    {
        $producto = $this->producto(stockM: 10);
        $admin = $this->adminOf($this->store);

        $pedidoId = $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 2, 'talla' => 'M'],
        ]))->assertCreated()->json('data.id');

        $this->resetTenant();
        $itemId = Pedido::withoutGlobalScopes()->find($pedidoId)->items()->withoutGlobalScopes()->value('id');

        $this->actingAs($admin)->putJson($this->storeUrl($this->store, "/admin/pedidos/{$pedidoId}/items/{$itemId}"), ['cantidad' => 5])
            ->assertOk()
            ->assertJsonPath('total', 500000);
        $this->assertSame(5, (int) $this->varianteM($producto)->stock);

        // Más de lo disponible => 422 y sin cambios
        $this->actingAs($admin)->putJson($this->storeUrl($this->store, "/admin/pedidos/{$pedidoId}/items/{$itemId}"), ['cantidad' => 50])
            ->assertStatus(422);
        $this->assertSame(5, (int) $this->varianteM($producto)->stock);

        $this->actingAs($admin)->deleteJson($this->storeUrl($this->store, "/admin/pedidos/{$pedidoId}/items/{$itemId}"))
            ->assertOk();
        $this->assertSame(10, (int) $this->varianteM($producto)->stock);
    }

    public function test_whatsapp_order_uses_server_prices_and_reserves_stock(): void
    {
        $producto = $this->producto(['precio' => 40000], stockM: 4);
        $sessionId = $this->withFixedSession();

        $carrito = Carrito::withoutGlobalScopes()->create(['sesion_id' => $sessionId, 'estado' => 'ACTIVO']);
        $carrito->forceFill(['store_id' => $this->store->id])->save();
        CarritoItem::withoutGlobalScopes()->create([
            'carrito_id' => $carrito->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'talla' => 'M',
        ])->forceFill(['store_id' => $this->store->id])->save();

        $response = $this->postJson($this->storeUrl($this->store, '/api/carrito/pedir-whatsapp'))
            ->assertCreated()
            ->assertJsonPath('data.total', 120000);

        $pedido = Pedido::withoutGlobalScopes()->findOrFail($response->json('data.id'));
        $this->assertSame('whatsapp', $pedido->origen);
        $this->assertSame(1, (int) $this->varianteM($producto)->stock);
        $this->assertSame(0, CarritoItem::withoutGlobalScopes()->where('carrito_id', $carrito->id)->count());
    }

    public function test_order_endpoints_are_rate_limited(): void
    {
        $producto = $this->producto();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
                ['producto_id' => $producto->id, 'cantidad' => 1],
            ]))->assertCreated();
        }

        $this->postJson($this->storeUrl($this->store, '/api/pedidos'), $this->datosCliente([
            ['producto_id' => $producto->id, 'cantidad' => 1],
        ]))->assertStatus(429);
    }
}
