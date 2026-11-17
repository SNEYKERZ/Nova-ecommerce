<?php

namespace Tests;

use App\Models\Store;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Salvaguarda: nunca ejecutar tests (RefreshDatabase) contra una BD real.
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Los tests deben ejecutarse sobre SQLite :memory: (actual: {$connection} / {$database}). ".
                'Revisa phpunit.xml y que no haya config cacheada.'
            );
        }

        return parent::setUpTraits();
    }

    /**
     * URL absoluta dentro del subdominio de una tienda (tienda.vendex.test).
     */
    protected function storeUrl(Store $store, string $path = '/'): string
    {
        return 'http://'.$store->slug.'.'.config('vendex.domain').$path;
    }

    protected function centralUrl(string $path = '/'): string
    {
        return 'http://'.config('vendex.domain').$path;
    }

    protected function adminOf(Store $store): User
    {
        return User::factory()->admin($store)->create();
    }

    /**
     * Usa siempre el mismo ID de sesión en los requests siguientes (el carrito
     * es por sesión y el cliente de tests no conserva cookies entre requests).
     */
    protected function withFixedSession(): string
    {
        $sessionId = Str::random(40);
        $this->withCredentials()->withCookie(config('session.cookie'), $sessionId);

        return $sessionId;
    }

    /**
     * Limpia el tenant que haya quedado fijado por un request anterior, para
     * que las consultas de aserción no queden filtradas por tienda.
     */
    protected function resetTenant(): void
    {
        app(TenantManager::class)->clear();
    }
}
