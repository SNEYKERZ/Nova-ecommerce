<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'demo')->first();
        
        if (!$store) {
            $this->command->warn('No se encontró el store demo.');
            return;
        }

        // Ignorar el scope de tenant para el seeder
        User::ignoreTenantScope();

        $users = [
            [
                'name' => 'Super Admin Vendex',
                'email' => 'superadmin@vendex.app',
                'role' => 'super_admin',
                'password' => 'Vendex2026*',
                'store_id' => null, // Super admin no tiene store
            ],
            [
                'name' => 'Admin Demo Store',
                'email' => 'admin@demo.vendex.app',
                'role' => 'admin',
                'password' => 'Vendex2026*',
                'store_id' => $store->id,
            ],
            [
                'name' => 'Cliente Demo',
                'email' => 'cliente@demo.vendex.app',
                'role' => 'cliente',
                'password' => 'Vendex2026*',
                'store_id' => $store->id,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => $user['password'],
                    'store_id' => $user['store_id'],
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );
        }

        User::restoreTenantScope();
    }
}
