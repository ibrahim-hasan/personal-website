<?php

namespace Tests\Feature\Filament;

use App\Models\ContactInquiry;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactInquiryReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_review_the_reference_role_and_timing_without_submission_hash(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $inquiry = ContactInquiry::factory()->create([
            'public_reference' => 'IH-0A1B2C3D4E5F',
            'role' => 'Operations Director',
            'timing' => 'We are planning the first workshop next quarter.',
            'submission_hash' => 'private-submission-hash-that-must-not-render',
        ]);

        $this->actingAs($admin)
            ->get('/admin/contact-inquiries')
            ->assertOk()
            ->assertSee($inquiry->public_reference)
            ->assertSee($inquiry->role)
            ->assertSee($inquiry->timing)
            ->assertDontSee('private-submission-hash-that-must-not-render');

        $this->actingAs($admin)
            ->get('/admin/contact-inquiries/'.$inquiry->getKey())
            ->assertOk()
            ->assertSee($inquiry->public_reference)
            ->assertSee($inquiry->role)
            ->assertSee($inquiry->timing)
            ->assertDontSee('private-submission-hash-that-must-not-render');

        $this->assertArrayNotHasKey('submission_hash', $inquiry->toArray());
    }
}
