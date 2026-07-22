<?php

namespace App\Console\Commands;

use App\Actions\Athar\SyncAtharPermissions as SyncAction;
use Illuminate\Console\Command;

class SyncAtharPermissions extends Command
{
    protected $signature = 'athar:sync-permissions';

    protected $description = 'Create Athar permissions and grant them to administrator roles';

    public function handle(SyncAction $sync): int
    {
        $permissions = $sync->handle();
        $this->components->info(sprintf('Synchronized %d Athar permissions for super administrators and administrators.', count($permissions)));

        return self::SUCCESS;
    }
}
