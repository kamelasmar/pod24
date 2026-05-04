<?php

namespace App\Console\Commands;

use App\Modules\Booking\Actions\ReleaseExpiredHolds;
use Illuminate\Console\Command;

class ReleaseExpiredHoldsCommand extends Command
{
    protected $signature = 'pod24:release-expired-holds';
    protected $description = 'Release booking holds whose 15-min window has elapsed';

    public function handle(ReleaseExpiredHolds $action): int
    {
        $count = $action->execute();
        $this->info("Released {$count} expired hold(s).");
        return self::SUCCESS;
    }
}
