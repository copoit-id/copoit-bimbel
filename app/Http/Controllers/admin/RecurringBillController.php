<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\BillInvoice;
use App\Models\BillInvoicePayment;
use App\Models\Package;
use App\Models\RecurringBill;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\RecurringBillService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecurringBillController extends Controller
{
    public function index(): View
    {
        $bills = RecurringBill::withCount(['targets', 'invoices'])
            ->latest()
            ->paginate(15);
        $invoices = BillInvoice::with('user:id,name,email')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.pages.recurring-bill.index', compact('bills', 'invoices'));
    }

    public function create(): View
    {
        $users = User::where('role', 'user')->orderBy('name')->get(['id', 'name', 'email']);
        $packages = $this->packageOptions();
        $studyGroups = $this->studyGroupOptions();
        $recurringBill = null;

        return view('admin.pages.recurring-bill.create', compact('users', 'packages', 'studyGroups', 'recurringBill'));
    }

    public function store(Request $request, RecurringBillService $billService): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $bill = DB::transaction(function () use ($request, $validated): RecurringBill {
            $bill = RecurringBill::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'amount' => $validated['amount'],
                'frequency' => $validated['frequency'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'due_day' => $validated['due_day'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => $request->user()?->id,
            ]);

            foreach ($validated['user_ids'] ?? [] as $userId) {
                $bill->targets()->create(['user_id' => $userId]);
            }

            foreach ($validated['class_ids'] ?? [] as $classId) {
                $bill->targets()->create(['class_id' => $classId]);
            }

            foreach ($validated['package_ids'] ?? [] as $packageId) {
                $bill->targets()->create(['package_id' => $packageId]);
            }

            foreach ($validated['study_group_ids'] ?? [] as $studyGroupId) {
                $bill->targets()->create(['study_group_id' => $studyGroupId]);
            }

            return $bill;
        });

        $billService->generateInvoices($bill, now()->addMonth());

        return redirect()
            ->route('admin.recurring-bills.show', $bill)
            ->with('success', 'Tagihan rutin berhasil dibuat.');
    }

    public function edit(RecurringBill $recurringBill): View
    {
        $users = User::where('role', 'user')->orderBy('name')->get(['id', 'name', 'email']);
        $packages = $this->packageOptions();
        $studyGroups = $this->studyGroupOptions();
        $selectedUserIds = $recurringBill->targets()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();
        $selectedPackageIds = $recurringBill->targets()
            ->whereNotNull('package_id')
            ->pluck('package_id')
            ->all();
        $selectedStudyGroupIds = $recurringBill->targets()
            ->whereNotNull('study_group_id')
            ->pluck('study_group_id')
            ->all();

        return view('admin.pages.recurring-bill.create', compact(
            'users',
            'packages',
            'studyGroups',
            'recurringBill',
            'selectedUserIds',
            'selectedPackageIds',
            'selectedStudyGroupIds',
        ));
    }

    public function update(Request $request, RecurringBill $recurringBill): RedirectResponse
    {
        $validated = $this->validatedData($request);

        DB::transaction(function () use ($request, $recurringBill, $validated): void {
            $recurringBill->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'amount' => $validated['amount'],
                'frequency' => $validated['frequency'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'due_day' => $validated['due_day'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            // Target kelas yang mungkin sudah tersimpan tetap dipertahankan.
            $recurringBill->targets()->whereNotNull('user_id')->delete();
            foreach ($validated['user_ids'] ?? [] as $userId) {
                $recurringBill->targets()->create(['user_id' => $userId]);
            }

            $recurringBill->targets()->whereNotNull('package_id')->delete();
            foreach ($validated['package_ids'] ?? [] as $packageId) {
                $recurringBill->targets()->create(['package_id' => $packageId]);
            }

            $recurringBill->targets()->whereNotNull('study_group_id')->delete();
            foreach ($validated['study_group_ids'] ?? [] as $studyGroupId) {
                $recurringBill->targets()->create(['study_group_id' => $studyGroupId]);
            }
        });

        return redirect()
            ->route('admin.recurring-bills.show', $recurringBill)
            ->with('success', 'Tagihan rutin berhasil diperbarui.');
    }

    public function destroy(RecurringBill $recurringBill): RedirectResponse
    {
        DB::transaction(function () use ($recurringBill): void {
            $recurringBill->delete();
        });

        return redirect()
            ->route('admin.recurring-bills.index')
            ->with('success', 'Tagihan rutin berhasil dihapus. Invoice yang sudah dibuat tetap tersimpan sebagai riwayat.');
    }

    public function show(RecurringBill $recurringBill): View
    {
        $recurringBill->load(['targets.user', 'targets.class']);
        $paymentTotals = BillInvoicePayment::query()
            ->select('bill_invoice_id', DB::raw('SUM(amount) as paid_amount'))
            ->groupBy('bill_invoice_id');
        $periods = $recurringBill->invoices()
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join): void {
                $join->on('payment_totals.bill_invoice_id', '=', 'bill_invoices.id');
            })
            ->whereNotNull('bill_invoices.period_start')
            ->select([
                'bill_invoices.period_start',
                'bill_invoices.period_end',
                'bill_invoices.due_date',
            ])
            ->selectRaw('COUNT(*) as participant_count')
            ->selectRaw('SUM(bill_invoices.amount) as total_amount')
            ->selectRaw('COALESCE(SUM(payment_totals.paid_amount), 0) as paid_amount')
            ->groupBy('bill_invoices.period_start', 'bill_invoices.period_end', 'bill_invoices.due_date')
            ->orderByDesc('bill_invoices.period_start')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.recurring-bill.period-index', compact('recurringBill', 'periods'));
    }

    public function showPeriod(RecurringBill $recurringBill, string $periodStart): View
    {
        try {
            $periodStart = Carbon::parse($periodStart)->toDateString();
        } catch (\Throwable) {
            abort(404);
        }

        $recurringBill->load(['targets.user', 'targets.class']);
        $invoices = $recurringBill->invoices()
            ->with(['user:id,name,email', 'payments'])
            ->withSum('payments as paid_amount', 'amount')
            ->whereDate('period_start', $periodStart)
            ->orderByDesc('due_date')
            ->paginate(20);

        abort_if($invoices->isEmpty() && $invoices->currentPage() === 1, 404);

        $period = $invoices->first();
        $isPeriodDetail = true;

        return view('admin.pages.recurring-bill.show', compact('recurringBill', 'invoices', 'period', 'isPeriodDetail'));
    }

    public function generate(RecurringBill $recurringBill, RecurringBillService $billService): RedirectResponse
    {
        $created = $billService->generateInvoices($recurringBill, now()->addMonths(2));
        $billService->markOverdue();

        return back()->with('success', "Generate invoice selesai. Invoice baru: {$created}.");
    }

    public function recordPayment(Request $request, BillInvoice $invoice, RecurringBillService $billService): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice = $billService->recordPayment(
            $invoice,
            (int) $validated['amount'],
            $validated['payment_method'],
            $validated['notes'] ?? null,
            $request->user(),
        );

        return back()->with('success', $invoice->status === 'paid'
            ? 'Pembayaran tercatat dan tagihan sudah lunas.'
            : 'Cicilan berhasil dicatat. Sisa tagihan: Rp ' . number_format($invoice->remaining_amount, 0, ',', '.') . '.');
    }

    public function updateInvoice(Request $request, BillInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $paidAmount = (int) $invoice->payments()->sum('amount');
        if ((int) $validated['amount'] < $paidAmount) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Nominal invoice tidak boleh lebih kecil dari total pembayaran Rp ' . number_format($paidAmount, 0, ',', '.') . '.']);
        }

        DB::transaction(function () use ($invoice, $validated): void {
            $invoice = BillInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $paidAmount = (int) $invoice->payments()->sum('amount');

            if ((int) $validated['amount'] < $paidAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal invoice tidak boleh lebih kecil dari total pembayaran yang tercatat.',
                ]);
            }

            $status = $this->invoiceStatus($invoice, (int) $validated['amount'], $paidAmount, $validated['due_date']);
            $invoice->update([
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($invoice->paid_at ?? now()) : null,
                'paid_by' => $status === 'paid' ? $invoice->paid_by : null,
            ]);
        });

        return back()->with('success', 'Invoice berhasil diperbarui.');
    }

    public function destroyInvoice(BillInvoice $invoice): RedirectResponse
    {
        DB::transaction(function () use ($invoice): void {
            $invoice->delete();
        });

        return back()->with('success', 'Invoice berhasil dihapus beserta riwayat pembayarannya.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'in:daily,weekly,monthly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'distinct', 'exists:classes,class_id'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer', 'distinct', 'exists:packages,package_id'],
            'study_group_ids' => ['nullable', 'array'],
            'study_group_ids.*' => ['integer', 'distinct', 'exists:study_groups,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function packageOptions(): EloquentCollection
    {
        return Package::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['package_id', 'name', 'price']);
    }

    private function studyGroupOptions(): EloquentCollection
    {
        return StudyGroup::query()
            ->where('is_active', true)
            ->withCount('users')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function invoiceStatus(BillInvoice $invoice, int $amount, int $paidAmount, string $dueDate): string
    {
        if ($invoice->status === 'cancelled') {
            return 'cancelled';
        }

        if ($paidAmount >= $amount) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return Carbon::parse($dueDate)->startOfDay()->isBefore(today()) ? 'overdue' : 'unpaid';
    }
}
