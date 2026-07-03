<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            DemoContentSeeder::class,
            MediaSeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@ibrahimhasan.dev'],
            [
                'name' => 'Ibrahim Admin',
                'password' => Hash::make('Ah!#$%0455^&*'),
            ],
        );

        $user->assignRole('super_admin');
    }
}
