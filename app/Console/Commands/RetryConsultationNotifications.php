<?php

namespace App\Console\Commands;

use App\Services\Consultation\ConsultationNotificationDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('consultation:retry-notifications {--limit=50 : Maximum due notifications to queue}')]
#[Description('Queue due consultation notification retries without exposing inquiry details')]
class RetryConsultationNotifications extends Command
{
    public function handle(ConsultationNotificationDispatcher $notifications): int
    {
        $limit = (int) $this->option('limit');

        if ($limit < 1 || $limit > 500) {
            $this->components->error('The retry limit must be between 1 and 500.');

            return self::FAILURE;
        }

        $queued = $notifications->retryDue($limit);

        $this->components->info("Queued {$queued} consultation notification retries.");

        return self::SUCCESS;
    }
}
