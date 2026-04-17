<?php

namespace App\Console\Commands;

use App\Models\Store;
use Illuminate\Console\Command;

class CreateStore extends Command
{
    protected $signature = 'vendex:create-store {name : Nombre de la tienda} {--slug= : Slug único (opcional)} {--domain= : Dominio personalizado (opcional)}';

    protected $description = 'Crear una nueva tienda (tenant) en Vendex';

    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = $this->option('slug') ?? \Str::slug($name);

        // Verificar que el slug no exista
        if (Store::where('slug', $slug)->exists()) {
            $this->error("El slug '$slug' ya está en uso.");
            return self::FAILURE;
        }

        $domain = $this->option('domain');

        // Si hay dominio personalizado, verificar que no esté en uso
        if ($domain && Store::where('dominio', $domain)->exists()) {
            $this->error("El dominio '$domain' ya está en uso.");
            return self::FAILURE;
        }

        $store = Store::create([
            'slug' => $slug,
            'nombre' => $name,
            'dominio' => $domain,
            'activo' => true,
        ]);

        $this->info("Tienda creada exitosamente!");
        $this->line("Nombre: {$store->nombre}");
        $this->line("Slug: {$store->slug}");
        
        if ($store->dominio) {
            $this->line("Dominio: https://{$store->dominio}");
        } else {
            $this->line("URL: https://{$store->slug}.vendex.app");
        }

        return self::SUCCESS;
    }
}