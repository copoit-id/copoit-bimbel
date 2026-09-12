<?php

namespace App\Console\Commands;

use App\Services\TutorPackagePaymentService;
use Illuminate\Console\Command;

class GenerateTutorScheduleInvoices extends Command
{
    protected $signature = 'bills:generate-schedule';

    protected $description = 'Generate tagihan paket dari sesi jadwal yang sudah tiba.';

    public function handle(TutorPackagePaymentService $paymentService): int
    {
        $prepared = $paymentService->prepareDueInvoices();

        $this->info("Sesi jadwal diproses: {$prepared}.");

        return self::SUCCESS;
    }
}
