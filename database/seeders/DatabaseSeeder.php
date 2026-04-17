<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StoreSeeder::class,
            // StoreSettingSeeder ya no es necesario - la config está en stores
            CategoriaSeeder::class,
            ProductoSeeder::class,
            NoticiaSeeder::class,
            InsumoSeeder::class,
            UserSeeder::class,
        ]);
    }
}
