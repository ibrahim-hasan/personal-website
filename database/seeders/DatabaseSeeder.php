<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            SuperAdminSeeder::class,
            ServiceSeeder::class,
            ProjectSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
