<?php

namespace App\Console\Commands;

use App\Services\Operations\SchedulerHeartbeat;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('app:record-scheduler-heartbeat')]
#[Description('Record the scheduler heartbeat used by release readiness checks')]
class RecordSchedulerHeartbeat extends Command
{
    public function handle(SchedulerHeartbeat $heartbeat): int
    {
        try {
            if (! $heartbeat->record()) {
                $this->components->error('Scheduler heartbeat could not be recorded.');

                return self::FAILURE;
            }
        } catch (Throwable) {
            $this->components->error('Scheduler heartbeat could not be recorded.');

            return self::FAILURE;
        }

        $this->components->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
