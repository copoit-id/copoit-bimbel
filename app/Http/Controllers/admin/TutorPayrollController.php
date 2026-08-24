<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Tentor;
use App\Models\Package;
use App\Models\TutorAttendance;
use App\Models\TutorPayroll;
use App\Services\TutorPayrollService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TutorPayrollController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = $request->string('tab')->toString();
        $activeTab = in_array($activeTab, ['payroll', 'honor'], true) ? $activeTab : 'payroll';

        if ($activeTab === 'honor') {
            $honorTentors = Tentor::query()
                ->active()
                ->with('packageRates:tentor_id,package_id,amount')
                ->orderBy('name')
                ->paginate(\App\Support\Pagination::perPage(20), ['id', 'name', 'email', 'expertise', 'honor_per_attendance'], 'honor_page')
                ->withQueryString();

            $packages = Package::query()
                ->orderBy('name')
                ->get(['package_id', 'name']);

            return view('admin.pages.tutor-payroll.index', compact('activeTab', 'honorTentors', 'packages'));
        }

        $validated = $request->validate([
            'period_start' => ['nullable', 'date', 'required_with:period_end'],
            'period_end' => ['nullable', 'date', 'required_with:period_start', 'after_or_equal:period_start'],
        ]);
        $hasPeriodFilter = filled($validated['period_start'] ?? null);
        $periodStart = $hasPeriodFilter
            ? Carbon::parse($validated['period_start'])->startOfDay()
            : null;
        $periodEnd = $hasPeriodFilter
            ? Carbon::parse($validated['period_end'])->endOfDay()
            : null;

        $payrolls = TutorPayroll::query()
            ->with(['tentor:id,name,honor_per_attendance', 'items'])
            ->when($hasPeriodFilter, function ($query) use ($periodStart, $periodEnd): void {
                $query->where(function ($query) use ($periodStart, $periodEnd): void {
                    $query->where(function ($query) use ($periodStart, $periodEnd): void {
                        $query->whereDate('period_start', '<=', $periodEnd->toDateString())
                            ->whereDate('period_end', '>=', $periodStart->toDateString());
                    })->orWhereHas('items', function ($query) use ($periodStart, $periodEnd): void {
                        $query->whereBetween('session_date', [
                            $periodStart->toDateString(),
                            $periodEnd->toDateString(),
                        ]);
                    });
                });
            })
            ->orderBy('status')
            ->orderByDesc('period_end')
            ->orderBy('tentor_id')
            ->paginate(\App\Support\Pagination::perPage(20), ['*'], 'payroll_page')
            ->withQueryString();

        $pendingAttendanceCounts = TutorAttendance::query()
            ->where('approval_status', 'pending')
            ->when($hasPeriodFilter, fn ($query) => $query->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])))
            ->selectRaw('tentor_id, COUNT(*) as total')
            ->groupBy('tentor_id')
            ->pluck('total', 'tentor_id');
        $payrollTutorIds = $payrolls->getCollection()->pluck('tentor_id');
        $attendanceDetailsByTutor = TutorAttendance::query()
            ->with(['session.schedule:id,title', 'session.class:class_id,title'])
            ->whereIn('tentor_id', $payrollTutorIds)
            ->when($hasPeriodFilter, fn ($query) => $query->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])))
            ->orderByDesc('check_in_at')
            ->get()
            ->groupBy('tentor_id');

        $unprocessedAttendances = TutorAttendance::query()
            ->with(['tentor:id,name', 'session.schedule:id,title', 'session.class:class_id,title'])
            ->when($hasPeriodFilter, fn ($query) => $query->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])))
            ->where(function ($query): void {
                $query->where('approval_status', '!=', 'approved')
                    ->orWhereNotIn('status', ['present', 'late'])
                    ->orWhereDoesntHave('payrollItems');
            })
            ->orderByDesc('check_in_at')
            ->paginate(\App\Support\Pagination::perPage(10), ['*'], 'attendance_page')
            ->withQueryString();

        return view('admin.pages.tutor-payroll.index', compact(
            'payrolls',
            'activeTab',
            'periodStart',
            'periodEnd',
            'hasPeriodFilter',
            'pendingAttendanceCounts',
            'attendanceDetailsByTutor',
            'unprocessedAttendances',
        ));
    }

    public function generate(Request $request, TutorPayrollService $payrollService): RedirectResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'tentor_id' => ['required', 'exists:tentors,id'],
        ]);

        $periodStart = Carbon::parse($validated['period_start'])->startOfDay();
        $periodEnd = Carbon::parse($validated['period_end'])->endOfDay();
        $tentor = Tentor::query()->findOrFail($validated['tentor_id']);
        $payrollService->generate($tentor, $periodStart, $periodEnd, $request->user());

        return redirect()
            ->route('admin.tutor-payrolls.index', $validated)
            ->with('success', "Rekap penggajian {$tentor->name} berhasil dibuat/diperbarui.");
    }

    public function updateHonor(Request $request, TutorPayrollService $payrollService): RedirectResponse
    {
        $validated = $request->validate([
            'tentor_id' => ['required', 'exists:tentors,id'],
            'honor_per_attendance' => ['required', 'integer', 'min:1'],
        ]);

        $tentor = Tentor::query()->findOrFail($validated['tentor_id']);
        $tentor->update(['honor_per_attendance' => $validated['honor_per_attendance']]);
        $payrollService->syncApprovedAttendancesForTutor($tentor->fresh(), $request->user());

        return back()->with('success', "Honor {$tentor->name} berhasil diperbarui dan rekap absensi yang sudah disetujui telah disinkronkan otomatis.");
    }

    public function updatePackageRates(Request $request, TutorPayrollService $payrollService): RedirectResponse
    {
        $validated = $request->validate([
            'tentor_id' => ['required', 'exists:tentors,id'],
            'package_rates' => ['nullable', 'array'],
            'package_rates.*' => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ]);
        $packageRates = collect($validated['package_rates'] ?? [])
            ->filter(fn ($amount): bool => ! is_null($amount))
            ->mapWithKeys(fn ($amount, $packageId): array => [(int) $packageId => (int) $amount]);
        $existingPackageIds = Package::query()
            ->whereIn('package_id', $packageRates->keys())
            ->pluck('package_id')
            ->map(fn ($packageId): int => (int) $packageId);

        if ($existingPackageIds->count() !== $packageRates->count()) {
            return back()->withErrors(['package_rates' => 'Salah satu paket yang dipilih tidak ditemukan.']);
        }

        $tentor = Tentor::query()->findOrFail($validated['tentor_id']);

        DB::transaction(function () use ($tentor, $packageRates): void {
            $tentor->packageRates()
                ->when($packageRates->isNotEmpty(), fn ($query) => $query->whereNotIn('package_id', $packageRates->keys()))
                ->when($packageRates->isEmpty(), fn ($query) => $query)
                ->delete();

            foreach ($packageRates as $packageId => $amount) {
                $tentor->packageRates()->updateOrCreate(
                    ['package_id' => $packageId],
                    ['amount' => $amount]
                );
            }
        });

        $payrollService->syncApprovedAttendancesForTutor($tentor->fresh(), $request->user());

        return back()->with('success', "Tarif paket {$tentor->name} berhasil disimpan. Rekap yang belum lunas telah disinkronkan.");
    }

    public function update(Request $request, TutorPayroll $tutorPayroll, TutorPayrollService $payrollService): RedirectResponse
    {
        $tutorPayroll->loadMissing('tentor:id,honor_per_attendance');

        if (! $tutorPayroll->tentor || (int) $tutorPayroll->gross_amount < 1) {
            return back()->with('error', 'Tarif tutor belum menghasilkan nominal penggajian. Atur tarif paket atau honor default terlebih dahulu.');
        }

        $validated = $request->validate([
            'adjustment_amount' => ['required', 'integer', 'min:' . (-1 * (int) $tutorPayroll->gross_amount)],
            'status' => ['required', 'in:draft,paid'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $tutorPayroll, $validated, $payrollService): void {
            $wasPaid = $tutorPayroll->status === 'paid';
            $tutorPayroll->fill([
                'adjustment_amount' => $validated['adjustment_amount'],
                'net_amount' => (int) $tutorPayroll->gross_amount + (int) $validated['adjustment_amount'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'paid_by' => $validated['status'] === 'paid' ? ($wasPaid ? $tutorPayroll->paid_by : $request->user()->id) : null,
                'paid_at' => $validated['status'] === 'paid' ? ($wasPaid ? $tutorPayroll->paid_at : now()) : null,
            ]);
            $tutorPayroll->save();
            $payrollService->syncExpense($tutorPayroll, $request->user());
        });

        return back()->with('success', $validated['status'] === 'paid'
            ? 'Pembayaran tutor ditandai lunas dan otomatis masuk ke pengeluaran.'
            : 'Data penggajian tutor berhasil diperbarui.');
    }
}
