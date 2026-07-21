<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Tentor;
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
        $periodStart = Carbon::parse($request->input('period_start', now()->startOfMonth()->toDateString()))->startOfDay();
        $periodEnd = Carbon::parse($request->input('period_end', now()->endOfMonth()->toDateString()))->endOfDay();

        abort_if($periodEnd->lt($periodStart), 422, 'Periode penggajian tidak valid.');

        $payrolls = TutorPayroll::query()
            ->with(['tentor:id,name,honor_per_attendance', 'items'])
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->orderBy('status')
            ->orderBy('tentor_id')
            ->paginate(20)
            ->withQueryString();

        $tentors = Tentor::query()->active()->orderBy('name')->get(['id', 'name', 'honor_per_attendance']);
        $pendingAttendanceCounts = TutorAttendance::query()
            ->where('approval_status', 'pending')
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ]))
            ->selectRaw('tentor_id, COUNT(*) as total')
            ->groupBy('tentor_id')
            ->pluck('total', 'tentor_id');
        $payrollTutorIds = $payrolls->getCollection()->pluck('tentor_id');
        $attendanceDetailsByTutor = TutorAttendance::query()
            ->with(['session.schedule:id,title', 'session.class:class_id,title'])
            ->whereIn('tentor_id', $payrollTutorIds)
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ]))
            ->orderByDesc('check_in_at')
            ->get()
            ->groupBy('tentor_id');

        return view('admin.pages.tutor-payroll.index', compact(
            'payrolls',
            'periodStart',
            'periodEnd',
            'tentors',
            'pendingAttendanceCounts',
            'attendanceDetailsByTutor',
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

    public function update(Request $request, TutorPayroll $tutorPayroll): RedirectResponse
    {
        $validated = $request->validate([
            'adjustment_amount' => ['required', 'integer', 'min:' . (-1 * (int) $tutorPayroll->gross_amount)],
            'status' => ['required', 'in:draft,approved,paid'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $tutorPayroll, $validated): void {
            $tutorPayroll->fill([
                'adjustment_amount' => $validated['adjustment_amount'],
                'net_amount' => (int) $tutorPayroll->gross_amount + (int) $validated['adjustment_amount'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'paid_by' => $validated['status'] === 'paid' ? $request->user()->id : null,
                'paid_at' => $validated['status'] === 'paid' ? now() : null,
            ]);
            $tutorPayroll->save();
        });

        return back()->with('success', 'Data penggajian tutor berhasil diperbarui.');
    }
}
