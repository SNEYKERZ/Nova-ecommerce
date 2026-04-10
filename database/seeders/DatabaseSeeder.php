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
            StoreSettingSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            NoticiaSeeder::class,
            InsumoSeeder::class,
            UserSeeder::class,
        ]);
    }
}
