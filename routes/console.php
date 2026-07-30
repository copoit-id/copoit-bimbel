<?php

use App\Services\UtbkResultReleaseService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('utbk:release-results', function (UtbkResultReleaseService $service) {
    $service->releasePending();
    $this->info('IRT pending results processed.');
})->purpose('Release IRT results once jadwal berakhir');

Schedule::command('utbk:release-results')->everyFiveMinutes();
