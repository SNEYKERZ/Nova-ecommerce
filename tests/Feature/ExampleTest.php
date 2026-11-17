<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_store_home_returns_a_successful_response(): void
    {
        $store = Store::factory()->create();

        $this->get($this->storeUrl($store))->assertStatus(200);
    }

    public function test_unknown_store_host_returns_404(): void
    {
        $this->get('http://no-existe.'.config('vendex.domain').'/')->assertNotFound();
    }
}
