<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Tentor;
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
            ->with(['tentor:id,name', 'items'])
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->orderBy('status')
            ->orderBy('tentor_id')
            ->paginate(20)
            ->withQueryString();

        $tentors = Tentor::query()->active()->orderBy('name')->get(['id', 'name']);

        return view('admin.pages.tutor-payroll.index', compact('payrolls', 'periodStart', 'periodEnd', 'tentors'));
    }

    public function generate(Request $request, TutorPayrollService $payrollService): RedirectResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'tentor_id' => ['required', 'exists:tentors,id'],
            'rate_per_attendance' => ['required', 'integer', 'min:0'],
        ]);

        $periodStart = Carbon::parse($validated['period_start'])->startOfDay();
        $periodEnd = Carbon::parse($validated['period_end'])->endOfDay();
        $tentor = Tentor::query()->findOrFail($validated['tentor_id']);
        $payrollService->generate($tentor, $periodStart, $periodEnd, (int) $validated['rate_per_attendance'], $request->user());

        return redirect()
            ->route('admin.tutor-payrolls.index', $validated)
            ->with('success', "Rekap penggajian {$tentor->name} berhasil dibuat/diperbarui.");
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
