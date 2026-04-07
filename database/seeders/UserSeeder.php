<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin Nova',
                'email' => 'superadmin@novaecommerce.com',
                'role' => 'super_admin',
                'password' => 'Nova2026*',
            ],
            [
                'name' => 'Admin Nova',
                'email' => 'admin@novaecommerce.com',
                'role' => 'admin',
                'password' => 'Nova2026*',
            ],
            [
                'name' => 'Cliente Demo',
                'email' => 'cliente@novaecommerce.com',
                'role' => 'cliente',
                'password' => 'Nova2026*',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => $user['password'],
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }
}
