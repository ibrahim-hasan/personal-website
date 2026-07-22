<?php

namespace Tests\Feature\Filament;

use App\Filament\Auth\RequestPasswordReset;
use App\Models\User;
use App\Notifications\Auth\AdminResetPasswordNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminForgotPasswordNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_password_reset_route_is_available(): void
    {
        $this->get('/admin/password-reset/request')
            ->assertOk()
            ->assertSee(__('admin.auth.forgot_password'));
    }

    public function test_admin_password_reset_notification_class_exists(): void
    {
        $this->assertTrue(class_exists(AdminResetPasswordNotification::class));
    }

    public function test_filament_resolves_the_branded_admin_reset_notification(): void
    {
        $notification = app(FilamentResetPasswordNotification::class, ['token' => 'admin-reset-token']);

        $this->assertInstanceOf(AdminResetPasswordNotification::class, $notification);
    }

    public function test_the_actual_filament_password_reset_flow_sends_the_branded_notification(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('super_admin');

        $this->bootAdminPanel();
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', $admin->email)
            ->call('request')
            ->assertHasNoFormErrors();

        Notification::assertSentTo(
            $admin,
            AdminResetPasswordNotification::class,
            function (AdminResetPasswordNotification $notification) use ($admin): bool {
                $mail = $notification->toMail($admin);

                return $notification instanceof ShouldQueue
                    && $mail->actionUrl === $notification->url
                    && str_contains($notification->url, '/admin/password-reset/reset')
                    && str_contains($notification->url, 'signature=');
            },
        );
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
