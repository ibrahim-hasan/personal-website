<?php

namespace Tests\Unit\ArticleAudio;

use App\Support\Ai\ElevenLabsExecutionBudget;
use LogicException;
use Tests\TestCase;

class ElevenLabsExecutionBudgetTest extends TestCase
{
    public function test_shared_budget_covers_every_segment_attempt_and_queue_visibility_window(): void
    {
        config()->set('services.elevenlabs.timeout', 420);
        config()->set('services.elevenlabs.connect_timeout', 15);
        config()->set('services.elevenlabs.max_segments', 3);
        config()->set('services.elevenlabs.job_timeout', 1560);
        config()->set('services.elevenlabs.unique_for', 1800);
        config()->set('services.elevenlabs.queue_connection', 'database');
        config()->set('services.elevenlabs.sample_characters', 650);
        config()->set('services.elevenlabs.models.eleven_v3.max_characters', 4500);
        config()->set('queue.connections.database.retry_after', 1620);

        $this->assertSame(420, ElevenLabsExecutionBudget::providerTimeout());
        $this->assertSame(15, ElevenLabsExecutionBudget::connectTimeout());
        $this->assertSame(1, ElevenLabsExecutionBudget::requestAttempts());
        $this->assertSame(420, ElevenLabsExecutionBudget::requestBudgetSeconds());
        $this->assertSame(1440, ElevenLabsExecutionBudget::minimumFullJobTimeout());
        $this->assertSame(1560, ElevenLabsExecutionBudget::fullJobTimeout());
        $this->assertSame(600, ElevenLabsExecutionBudget::sampleJobTimeout('eleven_v3'));
        $this->assertSame(1800, ElevenLabsExecutionBudget::uniqueFor(1560));
        $this->assertGreaterThan(
            ElevenLabsExecutionBudget::fullJobTimeout(),
            config('queue.connections.database.retry_after'),
        );
    }

    public function test_invalid_queue_visibility_configuration_fails_before_dispatch(): void
    {
        config()->set('services.elevenlabs.timeout', 150);
        config()->set('services.elevenlabs.max_segments', 3);
        config()->set('services.elevenlabs.job_timeout', 1560);
        config()->set('services.elevenlabs.queue_connection', 'database');
        config()->set('queue.connections.database.retry_after', 1560);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('retry_after value must exceed');

        ElevenLabsExecutionBudget::fullJobTimeout();
    }

    public function test_local_queue_worker_allows_the_full_audio_execution_budget(): void
    {
        config()->set('services.elevenlabs.timeout', 150);
        config()->set('services.elevenlabs.max_segments', 3);
        config()->set('services.elevenlabs.job_timeout', 1560);
        config()->set('services.elevenlabs.queue_connection', 'database');
        config()->set('queue.connections.database.retry_after', 1620);

        $composer = json_decode(
            (string) file_get_contents(base_path('composer.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $devCommand = collect($composer['scripts']['dev'] ?? [])->implode(' ');

        $this->assertSame(1, preg_match('/queue:listen[^\"]*--timeout=(\d+)/', $devCommand, $matches));
        $this->assertGreaterThanOrEqual(
            ElevenLabsExecutionBudget::fullJobTimeout(),
            (int) $matches[1],
        );
    }
}
