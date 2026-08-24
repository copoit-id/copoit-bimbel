<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Check AI correction status every minute
        $schedule->command('essay:check-ai-status')->everyMinute();
        $schedule->command('class-sessions:generate --days=60')->dailyAt('00:10');
        $schedule->command('bills:generate-recurring --months=1')->dailyAt('00:20');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
