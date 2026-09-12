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
use App\Services\TutorPackagePaymentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecurringBillController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $source = $request->string('source')->toString();
        $source = in_array($source, [BillInvoice::SOURCE_MANUAL, BillInvoice::SOURCE_SCHEDULE], true) ? $source : null;
        $invoiceGroups = BillInvoice::query()
            ->leftJoin('packages', 'packages.package_id', '=', 'bill_invoices.package_id')
            ->leftJoin('study_groups', 'study_groups.id', '=', 'bill_invoices.study_group_id')
            ->leftJoin('recurring_bills', 'recurring_bills.id', '=', 'bill_invoices.recurring_bill_id')
            ->when($source, fn ($query) => $query->where('bill_invoices.billing_source', $source))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('bill_invoices.invoice_number', 'like', "%{$search}%")
                        ->orWhere('bill_invoices.title', 'like', "%{$search}%")
                        ->orWhere('packages.name', 'like', "%{$search}%")
                        ->orWhere('study_groups.name', 'like', "%{$search}%")
                        ->orWhere('recurring_bills.name', 'like', "%{$search}%");
                });
            })
            ->select([
                'bill_invoices.billing_source',
                'bill_invoices.recurring_bill_id',
                'bill_invoices.package_id',
                'bill_invoices.study_group_id',
            ])
            ->selectRaw('MAX(bill_invoices.title) as title')
            ->selectRaw('MAX(packages.name) as package_name')
            ->selectRaw('MAX(study_groups.name) as study_group_name')
            ->selectRaw('MAX(recurring_bills.name) as recurring_bill_name')
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('MAX(bill_invoices.period_start) as latest_period_start')
            ->groupBy([
                'bill_invoices.billing_source',
                'bill_invoices.recurring_bill_id',
                'bill_invoices.package_id',
                'bill_invoices.study_group_id',
            ])
            ->orderByDesc('latest_period_start')
            ->paginate(\App\Support\Pagination::perPage(20), ['*'], 'invoice_page')
            ->withQueryString()
            ->through(fn (object $invoice): object => $this->presentInvoiceGroupRow($invoice));

        return view('admin.pages.recurring-bill.index', compact('invoiceGroups', 'search', 'source'));
    }

    private function presentInvoiceGroupRow(object $invoice): object
    {
        $isSchedule = $invoice->billing_source === BillInvoice::SOURCE_SCHEDULE;
        $invoice->source_label = $isSchedule ? 'Dari jadwal' : 'Manual';
        $invoice->source_variant = $isSchedule ? 'info' : 'warning';
        $invoice->group_label = $isSchedule
            ? 'Pembayaran '.trim(($invoice->package_name ?? 'Paket').' · '.($invoice->study_group_name ?? 'Rombel'))
            : ($invoice->recurring_bill_name ?? $invoice->title);
        $invoice->detail_route = $isSchedule
            ? route('admin.recurring-bills.schedule', [
                'studyGroup' => $invoice->study_group_id,
                'package' => $invoice->package_id,
            ])
            : ($invoice->recurring_bill_id
                ? route('admin.recurring-bills.show', $invoice->recurring_bill_id)
                : null);

        return $invoice;
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

    public function show(Request $request, RecurringBill $recurringBill): View
    {
        $recurringBill->load(['targets.user', 'targets.class']);
        $range = $this->billingRange($request);
        $paymentTotals = BillInvoicePayment::query()
            ->select('bill_invoice_id', DB::raw('SUM(amount) as paid_amount'))
            ->groupBy('bill_invoice_id');
        $periodQuery = $recurringBill->invoices()
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join): void {
                $join->on('payment_totals.bill_invoice_id', '=', 'bill_invoices.id');
            })
            ->whereNotNull('bill_invoices.period_start');
        $this->applyBillingRange($periodQuery, $range);
        $periods = $periodQuery
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
            ->paginate(\App\Support\Pagination::perPage(20))
            ->withQueryString()
            ->through(function (object $period) use ($recurringBill): object {
                $period->amount = (int) $period->total_amount;
                $period->detail_route = route('admin.recurring-bills.periods.show', [$recurringBill, $period->period_start]);

                return $this->presentBillingPeriod($period);
            });

        $billing = [
            'title' => $recurringBill->name,
            'description' => $recurringBill->targets->count().' peserta sasaran · '.$recurringBill->frequencyLabel(),
            'source_label' => 'Manual',
            'source_variant' => 'warning',
            'cycle_label' => $recurringBill->frequencyLabel(),
            'back_route' => route('admin.recurring-bills.index'),
        ];
        $periodTabs = $this->billingPeriodTabs('admin.recurring-bills.show', ['recurringBill' => $recurringBill], $range);

        return view('admin.pages.recurring-bill.period-index', compact('billing', 'periods', 'periodTabs'));
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
            ->paginate(\App\Support\Pagination::perPage(20));

        abort_if($invoices->isEmpty() && $invoices->currentPage() === 1, 404);

        $period = $this->presentBillingPeriod($invoices->first());
        $billing = [
            'title' => $recurringBill->name,
            'description' => 'Status setiap peserta dan riwayat penerimaan pada periode tagihan ini.',
            'source_label' => 'Manual',
            'source_variant' => 'warning',
            'cycle_label' => $recurringBill->frequencyLabel(),
            'back_route' => route('admin.recurring-bills.show', $recurringBill),
        ];

        return view('admin.pages.recurring-bill.period-detail', compact('billing', 'invoices', 'period'));
    }

    public function showSchedulePeriod(StudyGroup $studyGroup, Package $package, string $periodStart, ?int $classSession = null): View
    {
        try {
            $periodStart = Carbon::parse($periodStart)->toDateString();
        } catch (\Throwable) {
            abort(404);
        }

        $invoices = BillInvoice::query()
            ->with([
                'user:id,name,email',
                'payments.paidBy:id,name',
                'package:package_id,name,tutor_payment_frequency',
                'studyGroup:id,name',
                'classSession:id,class_schedule_id,tentor_id,session_date,start_at',
                'classSession.schedule:id,title',
                'classSession.tentor:id,name',
            ])
            ->withSum('payments as paid_amount', 'amount')
            ->where('billing_source', BillInvoice::SOURCE_SCHEDULE)
            ->where('study_group_id', $studyGroup->id)
            ->where('package_id', $package->package_id)
            ->whereDate('period_start', $periodStart)
            ->when($classSession, fn ($query) => $query->where('class_session_id', $classSession))
            ->orderBy('user_id')
            ->paginate(\App\Support\Pagination::perPage(30))
            ->withQueryString();

        abort_if($invoices->isEmpty() && $invoices->currentPage() === 1, 404);

        $period = $this->presentBillingPeriod($invoices->first());
        $billing = [
            'title' => 'Pembayaran '.$package->name,
            'description' => 'Status setiap peserta dan riwayat penerimaan pada periode tagihan ini.',
            'source_label' => 'Dari jadwal',
            'source_variant' => 'info',
            'cycle_label' => TutorPackagePaymentService::billingFrequencyLabel($package->tutor_payment_frequency),
            'back_route' => route('admin.recurring-bills.schedule', [$studyGroup, $package]),
        ];

        return view('admin.pages.recurring-bill.period-detail', compact('billing', 'invoices', 'period'));
    }

    public function showSchedule(Request $request, StudyGroup $studyGroup, Package $package): View
    {
        $range = $this->billingRange($request);
        $paymentTotals = BillInvoicePayment::query()
            ->selectRaw('bill_invoice_id, COALESCE(SUM(amount), 0) as paid_amount')
            ->groupBy('bill_invoice_id');
        $periodQuery = BillInvoice::query()
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join): void {
                $join->on('payment_totals.bill_invoice_id', '=', 'bill_invoices.id');
            })
            ->where('bill_invoices.billing_source', BillInvoice::SOURCE_SCHEDULE)
            ->where('bill_invoices.study_group_id', $studyGroup->id)
            ->where('bill_invoices.package_id', $package->package_id);
        $this->applyBillingRange($periodQuery, $range);
        $periods = $periodQuery
            ->select(['bill_invoices.class_session_id', 'bill_invoices.period_start', 'bill_invoices.period_end', 'bill_invoices.due_date'])
            ->selectRaw('COUNT(*) as participant_count')
            ->selectRaw('COALESCE(SUM(bill_invoices.amount), 0) as amount')
            ->selectRaw('COALESCE(SUM(payment_totals.paid_amount), 0) as paid_amount')
            ->groupBy('bill_invoices.class_session_id', 'bill_invoices.period_start', 'bill_invoices.period_end', 'bill_invoices.due_date')
            ->orderByDesc('bill_invoices.period_start')
            ->paginate(\App\Support\Pagination::perPage(20))
            ->withQueryString()
            ->through(function (object $period) use ($studyGroup, $package): object {
                $period->remaining_amount = max(0, (int) $period->amount - (int) $period->paid_amount);
                $period->detail_route = route('admin.recurring-bills.schedule-period', [
                    'studyGroup' => $studyGroup->id,
                    'package' => $package->package_id,
                    'periodStart' => $period->period_start,
                    'classSession' => $period->class_session_id,
                ]);

                return $this->presentBillingPeriod($period);
            });
        $billing = [
            'title' => 'Pembayaran '.$package->name,
            'description' => $studyGroup->name.' · Siklus tagihan mengikuti pengaturan paket.',
            'source_label' => 'Dari jadwal',
            'source_variant' => 'info',
            'cycle_label' => TutorPackagePaymentService::billingFrequencyLabel($package->tutor_payment_frequency),
            'back_route' => route('admin.recurring-bills.index'),
        ];
        $periodTabs = $this->billingPeriodTabs('admin.recurring-bills.schedule', ['studyGroup' => $studyGroup, 'package' => $package], $range);

        return view('admin.pages.recurring-bill.period-index', compact('billing', 'periods', 'periodTabs'));
    }

    private function billingRange(Request $request): string
    {
        $range = $request->string('range')->toString();

        return in_array($range, ['all', 'past', 'current', 'future'], true) ? $range : 'all';
    }

    private function applyBillingRange($query, string $range): void
    {
        match ($range) {
            'past' => $query->whereDate('bill_invoices.period_end', '<', today()->toDateString()),
            'current' => $query->whereDate('bill_invoices.period_start', '<=', today()->toDateString())
                ->whereDate('bill_invoices.period_end', '>=', today()->toDateString()),
            'future' => $query->whereDate('bill_invoices.period_start', '>', today()->toDateString()),
            default => null,
        };
    }

    private function billingPeriodTabs(string $routeName, array $parameters, string $activeRange): array
    {
        return collect([
            'all' => 'Semua',
            'past' => 'Sudah lewat',
            'current' => 'Berjalan',
            'future' => 'Mendatang',
        ])->map(fn (string $label, string $range): array => [
            'id' => $range,
            'label' => $label,
            'active' => $range === $activeRange,
            'href' => route($routeName, [...$parameters, 'range' => $range]),
        ])->values()->all();
    }

    private function presentBillingPeriod(object $period): object
    {
        $start = Carbon::parse($period->period_start);
        $end = Carbon::parse($period->period_end ?? $period->period_start);
        $today = today();
        $isPast = $end->lt($today);
        $isFuture = $start->gt($today);
        $period->label = $start->isSameDay($end)
            ? $start->translatedFormat('d M Y')
            : $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y');
        $period->state_label = $isPast ? 'Sudah lewat' : ($isFuture ? 'Mendatang' : 'Berjalan');
        $period->state_variant = $isPast ? 'secondary' : ($isFuture ? 'info' : 'success');
        $period->remaining_amount = max(0, (int) $period->amount - (int) $period->paid_amount);

        return $period;
    }

    /** Keep previously shared schedule URLs usable while the detail flow uses stable route parameters. */
    public function redirectLegacySchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'study_group_id' => ['required', 'integer', 'exists:study_groups,id'],
            'package_id' => ['required', 'integer', 'exists:packages,package_id'],
        ]);

        return to_route('admin.recurring-bills.schedule', [
            'studyGroup' => $validated['study_group_id'],
            'package' => $validated['package_id'],
        ]);
    }

    /** Keep previously shared period URLs usable while preserving per-session detail when supplied. */
    public function redirectLegacySchedulePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'study_group_id' => ['required', 'integer', 'exists:study_groups,id'],
            'package_id' => ['required', 'integer', 'exists:packages,package_id'],
            'period_start' => ['required', 'date'],
            'class_session_id' => ['nullable', 'integer', 'exists:class_sessions,id'],
        ]);

        return to_route('admin.recurring-bills.schedule-period', [
            'studyGroup' => $validated['study_group_id'],
            'package' => $validated['package_id'],
            'periodStart' => Carbon::parse($validated['period_start'])->toDateString(),
            'classSession' => $validated['class_session_id'] ?? null,
        ]);
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
