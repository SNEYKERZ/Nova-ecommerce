<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StoreSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            NoticiaSeeder::class,
            InsumoSeeder::class,
            UserSeeder::class,
            CarouselImagesSeeder::class,
        ]);
    }
}
