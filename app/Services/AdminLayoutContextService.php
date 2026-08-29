<?php

namespace App\Services;

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
}
