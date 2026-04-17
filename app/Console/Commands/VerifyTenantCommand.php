<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Console\Command;

class VerifyTenantCommand extends Command
{
    protected $signature = 'vendex:verify';
    protected $description = 'Verificar la configuración multi-tenant de Vendex';

    public function handle(): int
    {
        $this->info('=== Verificación de Vendex Multi-Tenant ===');
        $this->newLine();

        // Contar registros
        $this->info('Registros en la base de datos:');
        $this->line("- Stores: " . Store::count());
        $this->line("- Productos (global): " . Producto::count());
        $this->line("- Categorías (global): " . Categoria::count());
        $this->line("- Usuarios (global): " . User::count());

        // Sin scope (todos los stores)
        $this->newLine();
        $this->info('Sin tenant scope (todos los datos):');
        $this->line("- Productos sin scope: " . Producto::allStores()->count());
        $this->line("- Categorías sin scope: " . Categoria::allStores()->count());

        // Obtener store demo
        $store = Store::where('slug', 'demo')->first();

        if (!$store) {
            $this->error('No se encontró el store demo!');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Store Demo:');
        $this->line("- Nombre: {$store->nombre}");
        $this->line("- Slug: {$store->slug}");
        $this->line("- Dominio: {$store->dominio ?? 'No asignado'}");

        // Con scope (solo store demo)
        $this->newLine();
        $this->info('Con tenant scope (solo store demo):');
        $this->line("- Productos: " . Producto::forStore($store)->count());
        $this->line("- Categorías: " . Categoria::forStore($store)->count());

        // Usuarios
        $this->newLine();
        $this->info('Usuarios:');
        $users = User::allStores()->get(['name', 'email', 'role', 'store_id']);
        foreach ($users as $user) {
            $storeName = $user->store_id ? Store::find($user->store_id)?->nombre : 'Super Admin';
            $this->line("- {$user->name} ({$user->role}) - Store: {$storeName}");
        }

        $this->newLine();
        $this->info('✅ Verificación completada!');

        return self::SUCCESS;
    }
}