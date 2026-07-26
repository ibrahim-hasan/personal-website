<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Profile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMultiFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_mfa_credentials_are_encrypted_and_hidden_from_user_serialization(): void
    {
        $user = User::factory()->create();

        $user->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $user->app_authentication_recovery_codes = ['recovery-code'];
        $user->save();

        $storedUser = DB::table('users')
            ->where('id', $user->getKey())
            ->first([
                'app_authentication_secret',
                'app_authentication_recovery_codes',
            ]);

        $this->assertNotNull($storedUser);
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $storedUser->app_authentication_secret);
        $this->assertNotSame('recovery-code', $storedUser->app_authentication_recovery_codes);

        $user->refresh();

        $this->assertInstanceOf(HasAppAuthentication::class, $user);
        $this->assertInstanceOf(HasAppAuthenticationRecovery::class, $user);
        $this->assertSame('JBSWY3DPEHPK3PXP', $user->getAppAuthenticationSecret());
        $this->assertSame(['recovery-code'], $user->getAppAuthenticationRecoveryCodes());
        $this->assertArrayNotHasKey('app_authentication_secret', $user->toArray());
        $this->assertArrayNotHasKey('app_authentication_recovery_codes', $user->toArray());
    }

    public function test_the_admin_panel_uses_recoverable_totp_and_can_require_it_after_enrollment(): void
    {
        $this->bootAdminPanel();

        $panel = filament()->getPanel('admin');
        $providers = $panel->getMultiFactorAuthenticationProviders();

        $this->assertSame(Profile::class, $panel->getProfilePage());
        $this->assertArrayHasKey('app', $providers);
        $this->assertInstanceOf(AppAuthentication::class, $providers['app']);
        $this->assertTrue($providers['app']->isRecoverable());
        $this->assertFalse($panel->isMultiFactorAuthenticationRequired());

        config()->set('admin.mfa.required', true);

        $this->assertTrue($panel->isMultiFactorAuthenticationRequired());
    }

    public function test_filament_hashes_recovery_codes_before_they_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $provider = AppAuthentication::make();

        $provider->saveSecret($user, 'JBSWY3DPEHPK3PXP');
        $provider->saveRecoveryCodes($user, ['recovery-code']);

        $user->refresh();

        $recoveryCodes = $user->getAppAuthenticationRecoveryCodes();

        $this->assertIsArray($recoveryCodes);
        $this->assertCount(1, $recoveryCodes);
        $this->assertNotSame('recovery-code', $recoveryCodes[0]);
        $this->assertTrue(Hash::check('recovery-code', $recoveryCodes[0]));
    }

    public function test_the_admin_profile_includes_filaments_mfa_management_flow_without_affecting_reader_access(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole('super_admin');
        $reader = User::factory()->create();

        $this->bootAdminPanel();

        $this->assertFalse($reader->canAccessPanel(filament()->getPanel('admin')));

        Livewire::actingAs($administrator)
            ->test(Profile::class)
            ->assertSee(__('filament-panels::auth/pages/edit-profile.multi_factor_authentication.label'));
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
