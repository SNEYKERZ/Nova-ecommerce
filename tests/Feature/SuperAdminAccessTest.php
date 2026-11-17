<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_admin_cannot_access_any_super_admin_route(): void
    {
        $store = Store::factory()->create();
        $other = Store::factory()->create();
        $admin = $this->adminOf($store);
        $victim = User::factory()->admin($other)->create();

        $this->actingAs($admin);

        $this->get($this->centralUrl('/super-admin'))->assertForbidden();
        $this->getJson($this->centralUrl('/super-admin/reports'))->assertForbidden();
        $this->getJson($this->centralUrl('/super-admin/notifications'))->assertForbidden();
        $this->getJson($this->centralUrl("/super-admin/stores/{$other->id}/stats"))->assertForbidden();

        // Escalada de privilegios: crear un super admin
        $this->postJson($this->centralUrl('/super-admin/users'), [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);

        $this->putJson($this->centralUrl("/super-admin/users/{$victim->id}/password"), ['password' => 'nueva-clave-123'])
            ->assertForbidden();
        $this->putJson($this->centralUrl("/super-admin/stores/{$other->id}"), [
            'nombre' => 'X', 'slug' => 'x', 'activo' => false,
        ])->assertForbidden();
        $this->deleteJson($this->centralUrl("/super-admin/stores/{$other->id}"))->assertForbidden();
        $this->postJson($this->centralUrl("/super-admin/users/{$victim->id}/impersonate"))->assertForbidden();

        // También desde el subdominio de su propia tienda
        $this->getJson($this->storeUrl($store, '/super-admin/reports'))->assertForbidden();

        $this->assertTrue($other->fresh()->activo);
    }

    public function test_cliente_and_guest_cannot_access_panels(): void
    {
        $store = Store::factory()->create();
        $cliente = User::factory()->create(['store_id' => $store->id, 'role' => 'cliente']);

        $this->get($this->centralUrl('/super-admin'))->assertRedirect('/admin/login');

        $this->actingAs($cliente)->get($this->centralUrl('/super-admin'))->assertForbidden();
        $this->actingAs($cliente)->get($this->storeUrl($store, '/admin'))->assertForbidden();
    }

    public function test_super_admin_can_access_super_admin_routes(): void
    {
        $super = User::factory()->superAdmin()->create();
        Store::factory()->create();

        $this->actingAs($super)->get($this->centralUrl('/super-admin'))->assertOk();
        $this->actingAs($super)->getJson($this->centralUrl('/super-admin/reports'))->assertOk()->assertJsonPath('success', true);
        $this->actingAs($super)->getJson($this->centralUrl('/super-admin/notifications'))->assertOk();
    }

    public function test_updating_store_without_descripcion_keeps_existing_text(): void
    {
        $super = User::factory()->superAdmin()->create();
        $store = Store::factory()->create(['descripcion' => 'Texto de Nosotros']);

        $payload = [
            'nombre' => 'Tienda editada',
            'slug' => $store->slug,
            'activo' => true,
        ];

        $this->actingAs($super)->putJson($this->centralUrl("/super-admin/stores/{$store->id}"), $payload)->assertOk();
        $this->assertSame('Texto de Nosotros', $store->fresh()->descripcion);

        $this->actingAs($super)->putJson($this->centralUrl("/super-admin/stores/{$store->id}"), $payload + ['descripcion' => null])->assertOk();
        $this->assertNull($store->fresh()->descripcion);
    }

    public function test_legacy_md5_password_is_upgraded_on_login(): void
    {
        $super = User::factory()->superAdmin()->create();
        DB::table('users')->where('id', $super->id)->update(['password' => md5('ClaveVieja1')]);

        $this->post($this->centralUrl('/admin/login'), ['email' => $super->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post($this->centralUrl('/admin/login'), ['email' => $super->email, 'password' => 'ClaveVieja1'])
            ->assertRedirect('/super-admin');
        $this->assertAuthenticatedAs($super);
        $this->assertTrue(Hash::check('ClaveVieja1', $super->fresh()->getAuthPassword()));
    }

    public function test_super_admin_without_store_is_redirected_from_store_admin(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get($this->centralUrl('/admin'))->assertRedirect('/super-admin');
    }

    public function test_super_admin_cannot_demote_himself(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->putJson($this->centralUrl("/super-admin/users/{$super->id}"), [
            'name' => $super->name,
            'email' => $super->email,
            'role' => 'cliente',
        ])->assertStatus(422);

        $this->assertSame('super_admin', $super->fresh()->role);
    }

    public function test_impersonation_and_leave_flow(): void
    {
        $store = Store::factory()->create();
        $admin = $this->adminOf($store);
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->post($this->centralUrl("/super-admin/users/{$admin->id}/impersonate"))
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
        $this->assertSame($super->id, session('impersonator_id'));

        // El admin impersonado opera sobre SU tienda aunque esté en el dominio central
        $this->get($this->centralUrl('/admin'))->assertOk();

        $this->post($this->centralUrl('/admin/leave-impersonation'))->assertRedirect('/super-admin');
        $this->assertAuthenticatedAs($super);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_leave_impersonation_requires_auth_and_valid_impersonator(): void
    {
        $this->post($this->centralUrl('/admin/leave-impersonation'))->assertRedirect('/admin/login');
        $this->assertGuest();

        // Un impersonator_id que no corresponde a un super admin no da acceso
        $store = Store::factory()->create();
        $admin = $this->adminOf($store);
        $otherAdmin = $this->adminOf($store);

        $this->actingAs($admin)
            ->withSession(['impersonator_id' => $otherAdmin->id])
            ->post($this->storeUrl($store, '/admin/leave-impersonation'))
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $store = Store::factory()->create();
        $admin = $this->adminOf($store);

        $this->actingAs($admin)->post($this->storeUrl($store, '/admin/logout'))->assertRedirect('/');
        $this->assertGuest();
    }
}
