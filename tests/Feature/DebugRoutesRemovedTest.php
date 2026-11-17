<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DebugRoutesRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_routes_are_not_registered(): void
    {
        $this->assertFalse(Route::has('admin.test-inertia'));

        $uris = collect(Route::getRoutes()->getRoutes())->map->uri()->all();
        $this->assertNotContains('admin/test-inertia', $uris);
        $this->assertNotContains('admin/test-json', $uris);
    }

    public function test_debug_endpoints_return_404_even_for_admins(): void
    {
        $store = Store::factory()->create();
        $admin = $this->adminOf($store);

        $this->actingAs($admin)->get($this->storeUrl($store, '/admin/test-inertia'))->assertNotFound();
        $this->actingAs($admin)->get($this->storeUrl($store, '/admin/test-json'))->assertNotFound();

        $super = User::factory()->superAdmin()->create();
        $this->actingAs($super)->get($this->storeUrl($store, '/admin/test-json'))->assertNotFound();
    }
}
