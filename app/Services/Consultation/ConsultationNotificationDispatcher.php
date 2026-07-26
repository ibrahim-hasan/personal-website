<?php

namespace App\Services\Consultation;

use App\Jobs\SendConsultationNotification;
use App\Models\ContactInquiry;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConsultationNotificationDispatcher
{
    public function __construct(private readonly Dispatcher $bus) {}

    public function dispatch(ContactInquiry $inquiry): bool
    {
        if ($inquiry->notification_sent_at !== null) {
            return true;
        }

        $inquiry->forceFill([
            'notification_status' => 'queued',
            'notification_next_retry_at' => null,
        ])->saveQuietly();

        try {
            $this->bus->dispatch(
                (new SendConsultationNotification((int) $inquiry->getKey()))
                    ->onQueue($this->queueName())
                    ->afterCommit(),
            );
        } catch (Throwable) {
            $this->markDispatchFailure($inquiry);

            return false;
        }

        return true;
    }

    public function markAttempted(ContactInquiry $inquiry): void
    {
        $inquiry->forceFill([
            'notification_status' => 'sending',
            'notification_attempts' => (int) $inquiry->notification_attempts + 1,
            'notification_last_attempted_at' => now(),
            'notification_next_retry_at' => null,
        ])->saveQuietly();
    }

    public function markDelivered(ContactInquiry $inquiry): void
    {
        $inquiry->forceFill([
            'notification_status' => 'sent',
            'notification_sent_at' => now(),
            'notification_failed_at' => null,
            'notification_next_retry_at' => null,
        ])->saveQuietly();
    }

    public function markDeliveryFailure(int $inquiryId): void
    {
        $inquiry = ContactInquiry::query()->find($inquiryId);

        if ($inquiry === null || $inquiry->notification_sent_at !== null) {
            return;
        }

        $this->markFailure($inquiry, 'notification_delivery_failed');
    }

    public function retryDue(int $limit): int
    {
        $now = now();
        $maxAttempts = $this->maximumAttempts();
        $queued = 0;

        ContactInquiry::query()
            ->whereIn('notification_status', ['pending', 'failed'])
            ->where('notification_attempts', '<', $maxAttempts)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('notification_next_retry_at')
                    ->orWhere('notification_next_retry_at', '<=', $now);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (ContactInquiry $inquiry) use (&$queued): void {
                if ($this->dispatch($inquiry)) {
                    $queued++;
                }
            });

        return $queued;
    }

    private function markDispatchFailure(ContactInquiry $inquiry): void
    {
        $inquiry->forceFill([
            'notification_attempts' => (int) $inquiry->notification_attempts + 1,
        ])->saveQuietly();

        $this->markFailure($inquiry, 'notification_dispatch_failed');
    }

    private function markFailure(ContactInquiry $inquiry, string $event): void
    {
        $retryAt = (int) $inquiry->notification_attempts < $this->maximumAttempts()
            ? now()->addSeconds($this->retryDelaySeconds())
            : null;

        $inquiry->forceFill([
            'notification_status' => 'failed',
            'notification_failed_at' => now(),
            'notification_next_retry_at' => $retryAt,
        ])->saveQuietly();

        Log::critical('Consultation notification delivery requires attention.', [
            'channel' => 'consultation',
            'event' => $event,
            'retry_scheduled' => $retryAt !== null,
        ]);
    }

    private function maximumAttempts(): int
    {
        return max(1, (int) config('operations.consultation_notifications.max_attempts', 12));
    }

    private function queueName(): string
    {
        return trim((string) config('operations.consultation_notifications.queue', 'default')) ?: 'default';
    }

    private function retryDelaySeconds(): int
    {
        return max(1, (int) config('operations.consultation_notifications.retry_delay_seconds', 900));
    }
}
