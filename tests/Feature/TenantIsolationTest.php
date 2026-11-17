<?php

namespace Tests\Feature;

use App\Models\BloqueHome;
use App\Models\Categoria;
use App\Models\Cupon;
use App\Models\Gallery;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::factory()->create(['slug' => 'tienda-a']);
        $this->storeB = Store::factory()->create(['slug' => 'tienda-b']);
    }

    private function productoDe(Store $store, array $attrs = []): Producto
    {
        $categoria = Categoria::factory()->create(['store_id' => $store->id]);

        return Producto::factory()->create(array_merge([
            'store_id' => $store->id,
            'categoria_id' => $categoria->id,
        ], $attrs));
    }

    public function test_admin_cannot_modify_resources_of_another_store_by_id(): void
    {
        $admin = $this->adminOf($this->storeA);
        $productoB = $this->productoDe($this->storeB, ['precio' => 50000]);
        $cuponB = Cupon::factory()->create(['store_id' => $this->storeB->id]);
        $pedidoB = Pedido::factory()->create(['store_id' => $this->storeB->id]);
        $galleryB = Gallery::factory()->create(['store_id' => $this->storeB->id]);
        $categoriaA = Categoria::factory()->create(['store_id' => $this->storeA->id]);

        $this->actingAs($admin);
        $url = fn (string $path) => $this->storeUrl($this->storeA, $path);

        $this->putJson($url("/admin/productos/{$productoB->id}"), [
            'referencia' => 'HACK',
            'precio' => 1,
            'categoria_id' => $categoriaA->id,
            'estado' => 'DISPONIBLE',
        ])->assertNotFound();
        $this->deleteJson($url("/admin/productos/{$productoB->id}"))->assertNotFound();
        $this->postJson($url("/admin/productos/{$productoB->id}/toggle-coleccion"))->assertNotFound();

        $this->deleteJson($url("/admin/cupones/{$cuponB->id}"))->assertNotFound();
        $this->putJson($url("/admin/pedidos/{$pedidoB->id}/estado"), ['estado' => 'CANCELADO'])->assertNotFound();
        $this->putJson($url("/admin/pedidos/{$pedidoB->id}"), ['total' => 1])->assertNotFound();
        $this->deleteJson($url("/admin/galleries/{$galleryB->id}"))->assertNotFound();
        $this->getJson($url("/admin/galleries/{$galleryB->id}"))->assertNotFound();

        $this->assertSame('50000.00', (string) $productoB->fresh()->precio);
        $this->assertNotNull($cuponB->fresh());
        $this->assertSame('PENDIENTE', $pedidoB->fresh()->estado);
        $this->assertNotNull($galleryB->fresh());
    }

    public function test_admin_cannot_access_another_store_panel(): void
    {
        $admin = $this->adminOf($this->storeA);

        $this->actingAs($admin)->get($this->storeUrl($this->storeB, '/admin'))->assertForbidden();
        $this->actingAs($admin)->get($this->storeUrl($this->storeA, '/admin'))->assertOk();
    }

    public function test_admin_cannot_reference_foreign_ids_in_payloads(): void
    {
        $admin = $this->adminOf($this->storeA);
        $categoriaB = Categoria::factory()->create(['store_id' => $this->storeB->id]);
        $productoB = $this->productoDe($this->storeB);

        $this->actingAs($admin);

        $this->postJson($this->storeUrl($this->storeA, '/admin/productos'), [
            'referencia' => 'NUEVO-1',
            'precio' => 1000,
            'categoria_id' => $categoriaB->id,
            'estado' => 'DISPONIBLE',
        ])->assertStatus(422)->assertJsonValidationErrors('categoria_id');

        $this->postJson($this->storeUrl($this->storeA, '/admin/ofertas'), [
            'titulo' => 'Oferta',
            'producto_id' => $productoB->id,
            'descuento_porcentaje' => 10,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addWeek()->toDateString(),
            'esta_activa' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('producto_id');

        $pedidoA = Pedido::factory()->create(['store_id' => $this->storeA->id]);
        $this->postJson($this->storeUrl($this->storeA, "/admin/pedidos/{$pedidoA->id}/items"), [
            'producto_id' => $productoB->id,
            'cantidad' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('producto_id');
    }

    public function test_created_records_belong_to_the_admin_store(): void
    {
        $admin = $this->adminOf($this->storeA);
        $categoriaA = Categoria::factory()->create(['store_id' => $this->storeA->id]);

        $this->actingAs($admin)->postJson($this->storeUrl($this->storeA, '/admin/productos'), [
            'referencia' => 'MISMA-REF',
            'precio' => 1000,
            'categoria_id' => $categoriaA->id,
            'estado' => 'DISPONIBLE',
            'stock_por_talla' => [['talla' => 'M', 'stock' => 3]],
        ])->assertOk();

        $producto = Producto::withoutGlobalScopes()->where('referencia', 'MISMA-REF')->firstOrFail();
        $this->assertSame($this->storeA->id, (int) $producto->store_id);
        $this->assertSame($this->storeA->id, (int) ProductoVariante::withoutGlobalScopes()->where('producto_id', $producto->id)->value('store_id'));

        // La misma referencia (y SKU de variante) en otra tienda es válida
        $adminB = $this->adminOf($this->storeB);
        $categoriaB = Categoria::factory()->create(['store_id' => $this->storeB->id]);
        $this->actingAs($adminB)->postJson($this->storeUrl($this->storeB, '/admin/productos'), [
            'referencia' => 'MISMA-REF',
            'precio' => 1000,
            'categoria_id' => $categoriaB->id,
            'estado' => 'DISPONIBLE',
            'stock_por_talla' => [['talla' => 'M', 'stock' => 3]],
        ])->assertOk();

        // ...pero duplicada en la misma tienda devuelve 422 (antes: error 500 de BD)
        $this->actingAs($admin)->postJson($this->storeUrl($this->storeA, '/admin/productos'), [
            'referencia' => 'MISMA-REF',
            'precio' => 1000,
            'categoria_id' => $categoriaA->id,
            'estado' => 'DISPONIBLE',
        ])->assertStatus(422)->assertJsonValidationErrors('referencia');
    }

    public function test_home_blocks_are_isolated_per_store(): void
    {
        (new BloqueHome)->forceFill([
            'store_id' => $this->storeB->id,
            'posicion' => 1,
            'tipo' => 'texto',
            'titulo' => 'Bloque secreto de B',
            'activo' => true,
        ])->save();

        $this->get($this->storeUrl($this->storeA))
            ->assertOk()
            ->assertDontSee('Bloque secreto de B');

        // El admin de A guarda su bloque: no debe sobrescribir el de B
        $admin = $this->adminOf($this->storeA);
        $this->actingAs($admin)->postJson($this->storeUrl($this->storeA, '/admin/bloques'), [
            'posicion' => 1,
            'tipo' => 'texto',
            'titulo' => 'Bloque de A',
        ])->assertOk();

        $this->assertSame('Bloque secreto de B', BloqueHome::withoutGlobalScopes()->where('store_id', $this->storeB->id)->value('titulo'));
        $this->assertSame('Bloque de A', BloqueHome::withoutGlobalScopes()->where('store_id', $this->storeA->id)->value('titulo'));
    }

    public function test_storefront_only_shows_own_products(): void
    {
        $this->productoDe($this->storeA, ['nombre' => 'Gorra de A']);
        $this->productoDe($this->storeB, ['nombre' => 'Gorra de B']);
        $productoB = Producto::withoutGlobalScopes()->where('nombre', 'Gorra de B')->first();

        $this->get($this->storeUrl($this->storeA))
            ->assertOk()
            ->assertSee('Gorra de A')
            ->assertDontSee('Gorra de B');

        $this->get($this->storeUrl($this->storeA, "/productos/{$productoB->id}"))->assertNotFound();
    }

    public function test_admin_dashboard_search_does_not_crash(): void
    {
        $admin = $this->adminOf($this->storeA);

        // Antes buscaba en columnas inexistentes de insumos (codigo, referencia) => 500
        $this->actingAs($admin)->get($this->storeUrl($this->storeA, '/admin?search=abc'))->assertOk();
    }

    public function test_product_with_orders_is_archived_instead_of_deleted(): void
    {
        $admin = $this->adminOf($this->storeA);
        $producto = $this->productoDe($this->storeA);
        $pedido = Pedido::factory()->create(['store_id' => $this->storeA->id]);
        $pedido->items()->create([
            'store_id' => $this->storeA->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 1000,
            'subtotal' => 1000,
        ]);

        $this->actingAs($admin)->deleteJson($this->storeUrl($this->storeA, "/admin/productos/{$producto->id}"))
            ->assertOk()
            ->assertJsonPath('archived', true);

        $this->assertSame('NO_DISPONIBLE', $producto->fresh()->estado);
        $this->assertDatabaseCount('pedido_items', 1);
    }
}
