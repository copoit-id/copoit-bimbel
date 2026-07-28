<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageBookingRule;
use App\Models\Tentor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PackageBookingRuleController extends Controller
{
    public function edit(Package $package): RedirectResponse
    {
        return redirect()
            ->route('admin.class-schedules.index', [
                'package_id' => $package->package_id,
            ])
            ->with('info', 'Booking custom sekarang diatur langsung dari Kelas & Jadwal.');
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
            'delivery_mode' => ['required', Rule::in(['online', 'offline', 'hybrid'])],
            'learning_mode' => ['required', Rule::in(['personal', 'group', 'both'])],
            'min_participants' => ['required', 'integer', 'min:1', 'max:20'],
            'max_participants' => ['required', 'integer', 'min:1', 'max:20', 'gte:min_participants'],
            'default_location' => ['nullable', 'string', 'max:255'],
            'payment_deadline_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'price_tiers' => ['nullable', 'array', 'max:20'],
            'price_tiers.*.participant_count' => ['required', 'integer', 'min:1', 'max:20', 'distinct'],
            'price_tiers.*.price_per_person' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
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

        $priceTiers = collect($validated['price_tiers'] ?? [])
            ->filter(fn (array $tier): bool => $tier['price_per_person'] !== null)
            ->mapWithKeys(fn (array $tier): array => [
                (int) $tier['participant_count'] => (int) $tier['price_per_person'],
            ]);
        if (in_array($validated['learning_mode'], ['group', 'both'], true)) {
            $missingCounts = collect(range(
                (int) $validated['min_participants'],
                (int) $validated['max_participants']
            ))->diff($priceTiers->keys());

            if ($missingCounts->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'price_tiers' => 'Harga per orang wajib diisi untuk jumlah anggota: '.$missingCounts->join(', ').'.',
                ]);
            }
        }

        DB::transaction(function () use (
            $request,
            $package,
            $validated,
            $allowAllTutors,
            $tutorIds,
            $priceTiers
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
                    'delivery_mode' => $validated['delivery_mode'],
                    'learning_mode' => $validated['learning_mode'],
                    'min_participants' => $validated['min_participants'],
                    'max_participants' => $validated['max_participants'],
                    'default_location' => $validated['default_location'] ?? null,
                    'payment_deadline_hours' => $validated['payment_deadline_hours'],
                ]
            );

            $rule->tutors()->sync($allowAllTutors ? [] : $tutorIds);
            $rule->priceTiers()
                ->whereNotIn('participant_count', $priceTiers->keys()->all())
                ->delete();
            foreach ($priceTiers as $participantCount => $pricePerPerson) {
                $rule->priceTiers()->updateOrCreate(
                    ['participant_count' => $participantCount],
                    ['price_per_person' => $pricePerPerson]
                );
            }
        });

        return redirect()
            ->route('admin.package-booking.edit', $package)
            ->with('success', 'Pengaturan booking paket berhasil disimpan.');
    }
}
