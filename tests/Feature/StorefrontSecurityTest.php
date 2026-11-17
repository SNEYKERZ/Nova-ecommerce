<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create(['slug' => 'tienda']);
        $categoria = Categoria::factory()->create(['store_id' => $this->store->id]);
        $this->producto = Producto::factory()->create([
            'store_id' => $this->store->id,
            'categoria_id' => $categoria->id,
            'precio' => 30000,
        ]);
    }

    public function test_thank_you_page_is_only_visible_to_the_buyer_session(): void
    {
        $this->withFixedSession();

        $id = $this->postJson($this->storeUrl($this->store, '/api/pedidos'), [
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
            'direccion' => 'Calle 1',
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertCreated()->json('data.id');

        $this->get($this->storeUrl($this->store, "/pedido/gracias/{$id}"))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('ThankYouPage')->where('pedidoId', $id));

        // Otra sesión (otro visitante) no puede ver ni enumerar pedidos
        $this->flushSession();
        $this->withCookie(config('session.cookie'), Str::random(40));
        $this->get($this->storeUrl($this->store, "/pedido/gracias/{$id}"))->assertNotFound();
        $this->get($this->storeUrl($this->store, '/pedido/gracias/99999'))->assertNotFound();
    }

    public function test_thank_you_page_of_other_store_order_is_404_even_in_session(): void
    {
        $otra = Store::factory()->create();
        $pedidoAjeno = Pedido::factory()->create(['store_id' => $otra->id]);

        $this->withSession(['pedidos_realizados' => [$pedidoAjeno->id]])
            ->get($this->storeUrl($this->store, "/pedido/gracias/{$pedidoAjeno->id}"))
            ->assertNotFound();
    }

    public function test_cart_items_of_other_sessions_cannot_be_modified(): void
    {
        $carritoAjeno = Carrito::withoutGlobalScopes()->create(['sesion_id' => Str::random(40), 'estado' => 'ACTIVO']);
        $carritoAjeno->forceFill(['store_id' => $this->store->id])->save();
        $itemAjeno = CarritoItem::withoutGlobalScopes()->create([
            'carrito_id' => $carritoAjeno->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
        ]);
        $itemAjeno->forceFill(['store_id' => $this->store->id])->save();

        $this->withFixedSession();

        $this->putJson($this->storeUrl($this->store, "/api/carrito/actualizar/{$itemAjeno->id}"), ['cantidad' => 99])
            ->assertNotFound();
        $this->deleteJson($this->storeUrl($this->store, "/api/carrito/eliminar/{$itemAjeno->id}"))
            ->assertNotFound();

        $this->assertSame(1, (int) $itemAjeno->fresh()->cantidad);
    }

    public function test_cart_uses_server_prices_and_hides_session_id(): void
    {
        $this->withFixedSession();

        $this->postJson($this->storeUrl($this->store, '/api/carrito/agregar'), [
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio' => 1,
        ])->assertOk()->assertJsonMissingPath('data.carrito.sesion_id');

        $this->getJson($this->storeUrl($this->store, '/api/carrito'))
            ->assertOk()
            ->assertJsonPath('data.total', 60000)
            ->assertJsonPath('data.items.0.producto.precio', 30000);
    }

    public function test_products_of_other_stores_cannot_be_added_to_cart(): void
    {
        $otra = Store::factory()->create();
        $categoriaOtra = Categoria::factory()->create(['store_id' => $otra->id]);
        $ajeno = Producto::factory()->create(['store_id' => $otra->id, 'categoria_id' => $categoriaOtra->id]);

        $this->postJson($this->storeUrl($this->store, '/api/carrito/agregar'), ['producto_id' => $ajeno->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('producto_id');
    }

    public function test_coupon_endpoint_is_rate_limited(): void
    {
        $url = $this->storeUrl($this->store, '/api/carrito/aplicar-cupon');

        for ($i = 0; $i < 10; $i++) {
            $this->postJson($url, ['codigo' => 'NOPE'.$i])->assertStatus(422);
        }

        $this->postJson($url, ['codigo' => 'NOPE'])->assertStatus(429);
    }

    public function test_spoofed_localhost_host_does_not_enable_dev_mode_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('http://localhost/?store='.$this->store->slug)->assertNotFound();
    }
}
