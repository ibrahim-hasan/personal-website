<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $name = config('admin.name');
        $email = config('admin.email');
        $password = config('admin.password');

        if (! is_string($name) || trim($name) === '') {
            throw new \RuntimeException('ADMIN_NAME must be set before running SuperAdminSeeder.');
        }

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \RuntimeException('ADMIN_EMAIL must be a valid email address before running SuperAdminSeeder.');
        }

        if (! is_string($password) || strlen($password) < 16) {
            throw new \RuntimeException('ADMIN_PASSWORD must be at least 16 characters before running SuperAdminSeeder.');
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
                'locale_preference' => config('app.locale'),
            ]
        );

        $user->syncRoles(['super_admin']);
    }
}
