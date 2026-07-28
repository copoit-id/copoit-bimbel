<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageBookingRule;
use App\Models\ScheduleBookingRequest;
use App\Models\Tentor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PackageBookingRuleController extends Controller
{
    public function edit(Package $package): View
    {
        $package->load('bookingRule.tutors:id,name,expertise');
        $rule = $package->bookingRule ?? new PackageBookingRule([
            'is_enabled' => false,
            'session_quota' => 1,
            'duration_minutes' => 60,
            'min_notice_hours' => 12,
            'max_advance_days' => 30,
            'cancellation_hours' => 6,
            'allow_custom_time' => true,
            'allow_all_tutors' => true,
        ]);
        $tutors = Tentor::active()
            ->orderBy('name')
            ->get(['id', 'name', 'expertise']);
        $statusCounts = ScheduleBookingRequest::query()
            ->where('package_id', $package->package_id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.pages.package.booking.edit', compact(
            'package',
            'rule',
            'tutors',
            'statusCounts',
        ));
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'session_quota' => ['required', 'integer', 'min:1', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'min_notice_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'max_advance_days' => ['required', 'integer', 'min:1', 'max:365'],
            'cancellation_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'allow_custom_time' => ['nullable', 'boolean'],
            'allow_all_tutors' => ['nullable', 'boolean'],
            'tutor_ids' => ['nullable', 'array'],
            'tutor_ids.*' => ['integer', 'distinct', 'exists:tentors,id'],
        ]);
        $allowAllTutors = $request->boolean('allow_all_tutors');
        $tutorIds = collect($validated['tutor_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($request->boolean('is_enabled') && ! $allowAllTutors && $tutorIds === []) {
            throw ValidationException::withMessages([
                'tutor_ids' => 'Pilih minimal satu tutor atau aktifkan Semua Tutor.',
            ]);
        }
        if ($request->boolean('is_enabled')
            && $allowAllTutors
            && ! Tentor::active()->exists()) {
            throw ValidationException::withMessages([
                'allow_all_tutors' => 'Aktifkan minimal satu tutor sebelum mengaktifkan booking.',
            ]);
        }
        if (! $allowAllTutors
            && Tentor::active()->whereKey($tutorIds)->count() !== count($tutorIds)) {
            throw ValidationException::withMessages([
                'tutor_ids' => 'Semua tutor yang dipilih harus berstatus aktif.',
            ]);
        }

        DB::transaction(function () use (
            $request,
            $package,
            $validated,
            $allowAllTutors,
            $tutorIds
        ): void {
            $rule = PackageBookingRule::query()->updateOrCreate(
                ['package_id' => $package->package_id],
                [
                    'is_enabled' => $request->boolean('is_enabled'),
                    'session_quota' => $validated['session_quota'],
                    'duration_minutes' => $validated['duration_minutes'],
                    'min_notice_hours' => $validated['min_notice_hours'],
                    'max_advance_days' => $validated['max_advance_days'],
                    'cancellation_hours' => $validated['cancellation_hours'],
                    'allow_custom_time' => $request->boolean('allow_custom_time'),
                    'allow_all_tutors' => $allowAllTutors,
                ]
            );

            $rule->tutors()->sync($allowAllTutors ? [] : $tutorIds);
        });

        return redirect()
            ->route('admin.package-booking.edit', $package)
            ->with('success', 'Pengaturan booking paket berhasil disimpan.');
    }
}
