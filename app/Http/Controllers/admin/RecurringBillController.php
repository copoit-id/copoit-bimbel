<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\BillInvoice;
use App\Models\BillInvoicePayment;
use App\Models\ClassModel;
use App\Models\RecurringBill;
use App\Models\User;
use App\Services\RecurringBillService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $classes = ClassModel::orderBy('title')->get(['class_id', 'title']);

        return view('admin.pages.recurring-bill.create', compact('users', 'classes'));
    }

    public function store(Request $request, RecurringBillService $billService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'in:daily,weekly,monthly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'exists:classes,class_id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

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

        $billService->generateInvoices($bill, now()->addMonth());

        return redirect()
            ->route('admin.recurring-bills.show', $bill)
            ->with('success', 'Tagihan rutin berhasil dibuat.');
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
}
