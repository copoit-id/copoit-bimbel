<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageBookingRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PackageBookingRuleController extends Controller
{
    public function edit(Package $package): View|RedirectResponse
    {
        $rule = PackageBookingRule::query()
            ->with('priceTiers')
            ->where('package_id', $package->package_id)
            ->first();

        if (! $rule?->is_enabled) {
            return redirect()
                ->route('admin.class-schedules.index', ['package_id' => $package->package_id])
                ->with('info', 'Aktifkan request jadwal custom dari Kelas & Jadwal terlebih dahulu.');
        }

        $samePrice = old(
            'same_price',
            $rule->priceTiers->first()?->price_per_person ?? $package->price
        );
        $tierPrices = $rule->priceTiers
            ->mapWithKeys(fn ($tier): array => [$tier->participant_count => $tier->price_per_person])
            ->all();

        return view('admin.pages.package.booking.pricing', compact(
            'package',
            'rule',
            'samePrice',
            'tierPrices'
        ));
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'learning_mode' => ['required', Rule::in(['personal', 'group', 'both'])],
            'min_participants' => ['required_if:learning_mode,group,both', 'nullable', 'integer', 'min:1', 'max:20'],
            'max_participants' => ['required_if:learning_mode,group,both', 'nullable', 'integer', 'min:1', 'max:20', 'gte:min_participants'],
            'group_pricing_mode' => ['required_if:learning_mode,group,both', 'nullable', Rule::in(['same', 'tiered'])],
            'same_price' => ['required_if:group_pricing_mode,same', 'nullable', 'integer', 'min:0', 'max:999999999999'],
            'tier_prices' => ['nullable', 'array', 'max:20'],
            'tier_prices.*' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
        ]);

        if (($validated['learning_mode'] === 'group' || $validated['learning_mode'] === 'both')
            && $validated['group_pricing_mode'] === 'tiered') {
            $missingCounts = collect(range(
                (int) $validated['min_participants'],
                (int) $validated['max_participants']
            ))->filter(fn (int $count): bool => ! array_key_exists($count, $validated['tier_prices'] ?? [])
                || $validated['tier_prices'][$count] === null
                || $validated['tier_prices'][$count] === '');

            if ($missingCounts->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'tier_prices' => 'Harga per siswa wajib diisi untuk jumlah: '.$missingCounts->join(', ').'.',
                ]);
            }
        }

        DB::transaction(function () use ($package, $validated): void {
            $rule = PackageBookingRule::query()
                ->where('package_id', $package->package_id)
                ->lockForUpdate()
                ->firstOrFail();

            $learningMode = $validated['learning_mode'];
            $isGroupAvailable = in_array($learningMode, ['group', 'both'], true);
            $minParticipants = $isGroupAvailable ? (int) $validated['min_participants'] : 1;
            $maxParticipants = $isGroupAvailable ? (int) $validated['max_participants'] : 1;
            $pricingMode = $isGroupAvailable ? $validated['group_pricing_mode'] : 'same';

            $rule->update([
                'learning_mode' => $learningMode,
                'min_participants' => $minParticipants,
                'max_participants' => $maxParticipants,
                'group_pricing_mode' => $pricingMode,
            ]);

            if (! $isGroupAvailable) {
                $rule->priceTiers()->delete();

                return;
            }

            $participantCounts = range($minParticipants, $maxParticipants);
            $prices = collect($participantCounts)->mapWithKeys(function (int $count) use ($validated, $pricingMode): array {
                $price = $pricingMode === 'same'
                    ? $validated['same_price']
                    : ($validated['tier_prices'][$count] ?? null);

                return [$count => (int) $price];
            });

            $rule->priceTiers()
                ->whereNotIn('participant_count', $participantCounts)
                ->delete();
            foreach ($prices as $participantCount => $pricePerPerson) {
                $rule->priceTiers()->updateOrCreate(
                    ['participant_count' => $participantCount],
                    ['price_per_person' => $pricePerPerson]
                );
            }
        }, 3);

        return redirect()
            ->route('admin.package-booking.edit', $package)
            ->with('success', 'Pengaturan rombel dan harga per siswa berhasil disimpan.');
    }
}
