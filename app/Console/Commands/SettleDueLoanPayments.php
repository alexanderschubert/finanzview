<?php

namespace App\Console\Commands;

use App\Services\LoanSettlementService;
use Illuminate\Console\Command;

class SettleDueLoanPayments extends Command
{
    protected $signature = 'loans:settle-due';

    protected $description = 'Hakt fällige Kreditraten automatisch als bezahlt ab';

    public function handle(LoanSettlementService $service): int
    {
        $result = $service->settleDue();

        $this->info("{$result['payments']} Raten bei {$result['loans']} Krediten abgehakt.");

        return self::SUCCESS;
    }
}
