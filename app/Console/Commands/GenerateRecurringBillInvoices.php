<?php

namespace App\Console\Commands;

use App\Models\RecurringBill;
use App\Services\RecurringBillService;
use Illuminate\Console\Command;

class GenerateRecurringBillInvoices extends Command
{
    protected $signature = 'bills:generate-recurring {--months=1}';

    protected $description = 'Generate invoice dari tagihan rutin aktif.';

    public function handle(RecurringBillService $service): int
    {
        $months = max(1, (int) $this->option('months'));
        $created = 0;

        RecurringBill::query()
            ->where('is_active', true)
            ->chunkById(50, function ($bills) use ($service, $months, &$created) {
                foreach ($bills as $bill) {
                    $created += $service->generateInvoices($bill, now()->addMonths($months));
                }
            });

        $overdue = $service->markOverdue();
        $this->info("Invoice baru dibuat: {$created}. Invoice overdue diperbarui: {$overdue}.");

        return self::SUCCESS;
    }
}
