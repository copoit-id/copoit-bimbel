<?php

namespace App\Services;

use App\Models\GeneralPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdminNavigationService
{
    /**
     * Return only the public visibility flags required by the admin sidebar.
     */
    public function publicPageVisibility(): Collection
    {
        if (! Schema::hasTable('general_pages')) {
            return collect();
        }

        return GeneralPage::query()
            ->whereIn('page_key', ['landing', 'statistik-ptn', 'artikel'])
            ->pluck('is_active', 'page_key');
    }
}
