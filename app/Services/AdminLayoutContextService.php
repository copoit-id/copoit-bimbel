<?php

namespace App\Services;

use App\Models\Package;
use App\Models\TryoutDetail;

class AdminLayoutContextService
{
    public function questionPickerDetail(?int $tryoutDetailId): ?TryoutDetail
    {
        if (! $tryoutDetailId) {
            return null;
        }

        return TryoutDetail::query()
            ->select(['tryout_detail_id', 'tryout_id'])
            ->with('tryout:tryout_id,name')
            ->find($tryoutDetailId);
    }

    public function programSchedulePicker(?int $packageId): ?Package
    {
        if (! $packageId) {
            return null;
        }

        return Package::query()
            ->select(['package_id', 'name', 'enrollment_mode'])
            ->where('enrollment_mode', Package::ENROLLMENT_PROGRAM)
            ->find($packageId);
    }
}
