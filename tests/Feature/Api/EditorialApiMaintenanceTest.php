<?php

namespace Tests\Feature\Api;

use App\Models\EditorialApiAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EditorialApiMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_retention_purges_only_expired_events(): void
    {
        $expired = EditorialApiAuditLog::query()->create([
            'client_id' => (string) Str::uuid(),
            'request_id' => (string) Str::uuid(),
            'action' => 'article.created',
            'outcome' => 'success',
            'occurred_at' => now()->subDays(181),
        ]);
        $current = EditorialApiAuditLog::query()->create([
            'client_id' => (string) Str::uuid(),
            'request_id' => (string) Str::uuid(),
            'action' => 'article.created',
            'outcome' => 'success',
            'occurred_at' => now()->subDays(179),
        ]);

        $this->artisan('app:purge-editorial-api-audit-logs')
            ->expectsOutput('Purged 1 editorial API audit event(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('editorial_api_audit_logs', ['id' => $expired->getKey()]);
        $this->assertDatabaseHas('editorial_api_audit_logs', ['id' => $current->getKey()]);
    }

    public function test_api_cors_denies_unconfigured_browser_origins(): void
    {
        config()->set('cors.allowed_origins', []);

        $this->options('/api/v1/articles', [], ['Origin' => 'https://untrusted.example'])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
