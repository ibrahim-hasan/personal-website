<?php

namespace App\Console\Commands\Athar;

use App\Actions\Athar\ExpireAtharInvitations as ExpireAction;
use Illuminate\Console\Command;

class ExpireAtharInvitations extends Command
{
    protected $signature = 'athar:expire-invitations';

    protected $description = 'Mark past-due Athar invitations as expired';

    public function handle(ExpireAction $expire): int
    {
        $count = $expire->handle();
        $this->components->info(trans_choice('admin.messages.athar_expired', $count, ['count' => $count]));

        return self::SUCCESS;
    }
}
