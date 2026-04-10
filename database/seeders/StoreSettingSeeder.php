<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::updateOrCreate(
            ['id' => 1],
            [
                'store_name' => 'Nova Commerce',
                'logo_path' => null,
            ]
        );
    }
}
