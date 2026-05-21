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
        // Sin global scopes para el seeder
        User::withoutGlobalScopes();
        Store::withoutGlobalScopes();

        $stores = Store::all()->keyBy('slug');

        $demo = $stores->get('demo');
        $urban = $stores->get('urbanstyle');

        $users = [
            // Super Admin (sin store)
            [
                'name' => 'Super Admin Vendex',
                'email' => 'superadmin@vendez.app',
                'role' => 'super_admin',
                'password' => bcrypt('123456'),
                'store_id' => null,
            ],
        ];

        // Admin para tienda demo
        if ($demo) {
            $users[] = [
                'name' => 'Admin WebCaps',
                'email' => 'admin@webcaps.demo',
                'role' => 'admin',
                'password' => bcrypt('123456'),
                'store_id' => $demo->id,
            ];
            $users[] = [
                'name' => 'Cliente Demo',
                'email' => 'cliente@webcaps.demo',
                'role' => 'cliente',
                'password' => bcrypt('123456'),
                'store_id' => $demo->id,
            ];
        }

        // Admin para tienda urbanstyle
        if ($urban) {
            $users[] = [
                'name' => 'Admin Urban Style',
                'email' => 'admin@urbanstyle.store',
                'role' => 'admin',
                'password' => bcrypt('123456'),
                'store_id' => $urban->id,
            ];
            $users[] = [
                'name' => 'Cliente Urban',
                'email' => 'cliente@urbanstyle.store',
                'role' => 'cliente',
                'password' => bcrypt('123456'),
                'store_id' => $urban->id,
            ];
        }

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
    }
}
