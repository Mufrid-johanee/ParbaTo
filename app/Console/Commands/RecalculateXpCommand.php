<?php

namespace App\Console\Commands;

use App\Services\XpService;
use Illuminate\Console\Command;

class RecalculateXpCommand extends Command
{
    protected $signature = 'xp:recalculate';

    protected $description = 'Recompute users.xp and users.level from the XP ledger';

    public function handle(XpService $xp): int
    {
        $result = $xp->recalculateAll();
        $this->info("Updated {$result['updated']} student(s); corrected {$result['mismatches']} mismatch(es).");

        return self::SUCCESS;
    }
}
