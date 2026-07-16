<?php

namespace Tests\Feature\Filament;

use App\Notifications\Auth\AdminResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
