<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use App\Services\ClassScheduleService;
use Illuminate\Console\Command;

class GenerateClassSessions extends Command
{
    protected $signature = 'class-sessions:generate {--days=60}';

    protected $description = 'Generate sesi kelas dari jadwal rutin yang aktif.';

    public function handle(ClassScheduleService $service): int
    {
        $days = max(1, (int) $this->option('days'));
        $created = 0;

        ClassSchedule::query()
            ->where('is_active', true)
            ->chunkById(50, function ($schedules) use ($service, $days, &$created) {
                foreach ($schedules as $schedule) {
                    $created += $service->generateSessions($schedule, $days);
                }
            });

        $this->info("Sesi kelas baru dibuat: {$created}");

        return self::SUCCESS;
    }
}
