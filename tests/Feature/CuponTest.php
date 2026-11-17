<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\Cupon;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CuponTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Producto $producto;

    private string $sessionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create(['slug' => 'tienda']);
        $categoria = Categoria::factory()->create(['store_id' => $this->store->id]);
        $this->producto = Producto::factory()->create([
            'store_id' => $this->store->id,
            'categoria_id' => $categoria->id,
            'precio' => 100000,
        ]);
        $this->sessionId = $this->withFixedSession();
    }

    private function carrito(int $cantidad = 1, ?Cupon $cupon = null, float $descuentoGuardado = 0): Carrito
    {
        $carrito = Carrito::withoutGlobalScopes()->create([
            'sesion_id' => $this->sessionId,
            'estado' => 'ACTIVO',
            'cupon_id' => $cupon?->id,
            'descuento_cupon' => $descuentoGuardado,
        ]);
        $carrito->forceFill(['store_id' => $this->store->id])->save();

        CarritoItem::withoutGlobalScopes()->create([
            'carrito_id' => $carrito->id,
            'producto_id' => $this->producto->id,
            'cantidad' => $cantidad,
        ])->forceFill(['store_id' => $this->store->id])->save();

        return $carrito;
    }

    private function crearPedido(int $cantidad = 1): TestResponse
    {
        return $this->postJson($this->storeUrl($this->store, '/api/pedidos'), [
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
            'direccion' => 'Calle 1',
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => $cantidad]],
        ]);
    }

    public function test_valid_coupon_is_applied_and_its_usage_is_counted(): void
    {
        $cupon = Cupon::factory()->create(['store_id' => $this->store->id, 'codigo' => 'DESC10', 'valor_descuento' => 10, 'max_usos' => 5]);
        $this->carrito(2);

        // Código en minúsculas también funciona (se normaliza)
        $this->postJson($this->storeUrl($this->store, '/api/carrito/aplicar-cupon'), ['codigo' => 'desc10'])
            ->assertOk()
            ->assertJsonPath('data.descuento_cupon', 20000)
            ->assertJsonPath('data.total', 180000);

        $id = $this->crearPedido(2)->assertCreated()->json('data.id');

        $pedido = Pedido::withoutGlobalScopes()->findOrFail($id);
        $this->assertSame('180000.00', (string) $pedido->total);
        $this->assertSame('20000.00', (string) $pedido->descuento_cupon);
        $this->assertSame($cupon->id, (int) $pedido->cupon_id);
        $this->assertSame(1, $cupon->fresh()->usos_actuales);
    }

    public function test_discount_is_recomputed_against_the_real_order_subtotal(): void
    {
        // Descuento fijo guardado en el carrito "inflado" (manipulado o desactualizado)
        $cupon = Cupon::factory()->create(['store_id' => $this->store->id, 'tipo' => 'fijo', 'valor_descuento' => 5000]);
        $this->carrito(1, $cupon, 999999);

        $id = $this->crearPedido(1)->assertCreated()->json('data.id');

        $this->assertSame('95000.00', (string) Pedido::withoutGlobalScopes()->find($id)->total);
    }

    public function test_minimum_amount_is_enforced_at_order_time(): void
    {
        // Se aplicó con 3 unidades (300.000) pero el pedido final es de 1 unidad
        $cupon = Cupon::factory()->create(['store_id' => $this->store->id, 'monto_minimo' => 200000, 'valor_descuento' => 50]);
        $this->carrito(1, $cupon, 150000);

        $id = $this->crearPedido(1)->assertCreated()->json('data.id');

        $pedido = Pedido::withoutGlobalScopes()->find($id);
        $this->assertSame('100000.00', (string) $pedido->total);
        $this->assertNull($pedido->cupon_id);
        $this->assertSame(0, $cupon->fresh()->usos_actuales);
    }

    public function test_exhausted_coupon_is_not_applied(): void
    {
        $cupon = Cupon::factory()->create(['store_id' => $this->store->id, 'codigo' => 'AGOTADO', 'max_usos' => 1, 'usos_actuales' => 1]);
        $this->carrito(1, $cupon, 10000);

        $this->postJson($this->storeUrl($this->store, '/api/carrito/aplicar-cupon'), ['codigo' => 'AGOTADO'])
            ->assertStatus(422);

        $id = $this->crearPedido(1)->assertCreated()->json('data.id');
        $this->assertSame('100000.00', (string) Pedido::withoutGlobalScopes()->find($id)->total);
        $this->assertSame(1, $cupon->fresh()->usos_actuales);
    }

    public function test_expired_and_inactive_coupons_are_rejected(): void
    {
        Cupon::factory()->create(['store_id' => $this->store->id, 'codigo' => 'VENCIDO', 'fecha_expiracion' => now()->subDays(2)]);
        Cupon::factory()->create(['store_id' => $this->store->id, 'codigo' => 'INACTIVO', 'esta_activo' => false]);
        // Fecha sin hora = válido todo ese día
        Cupon::factory()->create(['store_id' => $this->store->id, 'codigo' => 'HOY', 'fecha_expiracion' => now()->startOfDay()]);
        $this->carrito(1);

        $url = $this->storeUrl($this->store, '/api/carrito/aplicar-cupon');
        $this->postJson($url, ['codigo' => 'VENCIDO'])->assertStatus(422)->assertJsonPath('message', 'Este cupón ha expirado.');
        $this->postJson($url, ['codigo' => 'INACTIVO'])->assertStatus(422);
        $this->postJson($url, ['codigo' => 'HOY'])->assertOk();
    }

    public function test_coupon_from_another_store_is_not_found(): void
    {
        $otra = Store::factory()->create();
        Cupon::factory()->create(['store_id' => $otra->id, 'codigo' => 'AJENO']);
        $this->carrito(1);

        $this->postJson($this->storeUrl($this->store, '/api/carrito/aplicar-cupon'), ['codigo' => 'AJENO'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El código ingresado no es válido.');
    }

    public function test_admin_cannot_create_percentage_coupon_over_100(): void
    {
        $admin = $this->adminOf($this->store);

        $this->actingAs($admin)->postJson($this->storeUrl($this->store, '/admin/cupones'), [
            'codigo' => 'MAS100',
            'tipo' => 'porcentaje',
            'valor_descuento' => 150,
        ])->assertStatus(422)->assertJsonValidationErrors('valor_descuento');

        $this->actingAs($admin)->postJson($this->storeUrl($this->store, '/admin/cupones'), [
            'codigo' => 'verano',
            'tipo' => 'porcentaje',
            'valor_descuento' => 15,
        ])->assertCreated();

        // Código normalizado y duplicado detectado sin importar mayúsculas
        $this->actingAs($admin)->postJson($this->storeUrl($this->store, '/admin/cupones'), [
            'codigo' => 'VERANO',
            'tipo' => 'fijo',
            'valor_descuento' => 1000,
        ])->assertStatus(422);
    }
}
