<?php

namespace App\Console\Commands;

use App\Modules\Customers\Actions\ExpireHourPacks;
use Illuminate\Console\Command;

class ExpireHourPacksCommand extends Command
{
    protected $signature = 'pod24:expire-hour-packs';

    protected $description = 'Expire un-expired hour-pack purchase rows past their expires_at';

    public function handle(ExpireHourPacks $action): int
    {
        $count = $action->execute();
        $this->info("Expired {$count} pack(s).");

        return self::SUCCESS;
    }
}
