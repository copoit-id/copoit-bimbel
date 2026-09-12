<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\BillInvoice;
use App\Models\BillInvoicePayment;
use App\Models\ClassSession;
use App\Services\RecurringBillService;
use App\Services\TutorPackagePaymentService;
use App\Services\TutorSchedulePeriodService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TutorPackagePaymentController extends Controller
{
    public function index(Request $request, TutorPackagePaymentService $paymentService, TutorSchedulePeriodService $periodService): View
    {
        $tentor = $request->user()->tentorProfile;
        $paymentRange = $request->string('range')->toString();
        $paymentRange = in_array($paymentRange, ['all', 'week', 'month'], true) ? $paymentRange : 'all';
        $paymentPeriod = $paymentRange === 'all' ? null : $periodService->resolve($request, $paymentRange);
        $sessions = ClassSession::query()
            ->with([
                'schedule:id,title',
                'schedule.packages:package_id,name,price,tutor_payment_frequency',
                'studyGroup:id,name,package_id',
                'studyGroup.package:package_id,name,price,tutor_payment_frequency',
            ])
            ->where('tentor_id', $tentor->id)
            ->whereNotNull('study_group_id')
            ->where(function ($query): void {
                $query
                    ->whereHas('studyGroup.package', fn ($packageQuery) => $packageQuery
                        ->whereIn('tutor_payment_frequency', TutorPackagePaymentService::BILLING_FREQUENCIES)
                        ->where('price', '>', 0))
                    ->orWhereHas('schedule.packages', fn ($packageQuery) => $packageQuery
                        ->whereIn('tutor_payment_frequency', TutorPackagePaymentService::BILLING_FREQUENCIES)
                        ->where('price', '>', 0));
            })
            ->when($paymentPeriod, fn ($query) => $query->whereBetween('session_date', [
                $paymentPeriod['start']->toDateString(), $paymentPeriod['end']->toDateString(),
            ]))
            ->latest('session_date')
            ->paginate(20)
            ->through(function (ClassSession $session) use ($paymentService): ClassSession {
                $session->setAttribute('payment_package', $paymentService->packageFor($session));

                return $session;
            });
        $paymentDays = $sessions->getCollection()
            ->groupBy(fn (ClassSession $session): string => $session->start_at->toDateString())
            ->map(function ($daySessions, string $date): array {
                $day = Carbon::parse($date);
                $isToday = $day->isToday();
                $isFuture = $day->isFuture();

                $tone = $isToday ? 'emerald' : ($isFuture ? 'blue' : 'rose');

                return [
                    'date' => $day,
                    'label' => $day->locale('id')->translatedFormat('l, d F Y'),
                    'tone' => $isToday ? 'emerald' : ($isFuture ? 'blue' : 'rose'),
                    'state' => $isToday ? 'Hari ini' : ($isFuture ? 'Mendatang' : 'Lewat'),
                    'card_class' => $tone === 'emerald' ? 'border-emerald-100 bg-emerald-50/40' : ($tone === 'blue' ? 'border-blue-100 bg-blue-50/40' : 'border-rose-100 bg-rose-50/40'),
                    'badge_class' => $tone === 'emerald' ? 'bg-emerald-100 text-emerald-700' : ($tone === 'blue' ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700'),
                    'date_class' => $tone === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($tone === 'blue' ? 'border-blue-100 bg-blue-50 text-blue-700' : 'border-rose-100 bg-rose-50 text-rose-700'),
                    'timeline_class' => $tone === 'emerald' ? 'bg-emerald-400 ring-emerald-50' : ($tone === 'blue' ? 'bg-blue-400 ring-blue-50' : 'bg-rose-400 ring-rose-50'),
                    'sessions' => $daySessions->values(),
                ];
            })->values();

        return view('tutor.package-payments.index', compact('tentor', 'sessions', 'paymentService', 'paymentRange', 'paymentPeriod', 'paymentDays') + [
            'periodYears' => $periodService->yearOptions(),
        ]);
    }

    public function prepare(Request $request, ClassSession $session, TutorPackagePaymentService $paymentService): RedirectResponse
    {
        $this->ensureAssignedPaymentSession($request, $session, $paymentService);
        $paymentService->prepareInvoices($session);

        return redirect()->route('tutor.package-payments.show', $session)
            ->with('success', 'Tagihan siswa untuk periode ini sudah disiapkan.');
    }

    public function show(Request $request, ClassSession $session, TutorPackagePaymentService $paymentService): View
    {
        $tentor = $request->user()->tentorProfile;
        $this->ensureAssignedPaymentSession($request, $session, $paymentService);
        $session->load([
            'schedule:id,title',
            'schedule.packages:package_id,name,price,tutor_payment_frequency',
            'studyGroup.package:package_id,name,price,tutor_payment_frequency',
        ]);
        $package = $paymentService->packageFor($session);
        $scopePrefix = 'tutor-package:'.$package->package_id.':'.$session->study_group_id.':';

        $currentInvoicesQuery = BillInvoice::query()
            ->with(['user:id,name,email', 'payments.paidBy:id,name'])
            ->where('payment_scope_key', 'like', $scopePrefix.'%')
            ->where('payment_scope_key', 'like', '%:'.$package->tutor_payment_frequency.':%');

        if ($package->tutor_payment_frequency === 'per_session') {
            $currentInvoicesQuery->where('class_session_id', $session->id);
        } elseif ($periodStart = $paymentService->periodStart($session, $package)) {
            $currentInvoicesQuery->whereDate('period_start', $periodStart);
        }

        $paymentSums = BillInvoicePayment::query()
            ->selectRaw('bill_invoice_id, COALESCE(SUM(amount), 0) as paid')
            ->groupBy('bill_invoice_id');
        $paymentTotals = (clone $currentInvoicesQuery)
            ->leftJoinSub($paymentSums, 'payment_sums', function ($join): void {
                $join->on('payment_sums.bill_invoice_id', '=', 'bill_invoices.id');
            })
            ->selectRaw('COUNT(bill_invoices.id) as invoice_count, COALESCE(SUM(bill_invoices.amount), 0) as amount, COALESCE(SUM(payment_sums.paid), 0) as paid, COALESCE(SUM(GREATEST(bill_invoices.amount - COALESCE(payment_sums.paid, 0), 0)), 0) as remaining')
            ->first();
        $paymentSummary = [
            'invoice_count' => (int) $paymentTotals->invoice_count,
            'amount' => (int) $paymentTotals->amount,
            'paid' => (int) $paymentTotals->paid,
            'remaining' => (int) $paymentTotals->remaining,
        ];
        $currentInvoices = $currentInvoicesQuery
            ->orderBy('user_id')
            ->paginate(30, ['*'], 'current_page');

        $historyInvoices = BillInvoice::query()
            ->with(['user:id,name,email', 'payments.paidBy:id,name'])
            ->where('package_id', $package->package_id)
            ->where('study_group_id', $session->study_group_id)
            ->latest('period_start')
            ->latest('id')
            ->paginate(20, ['*'], 'history_page');

        return view('tutor.package-payments.show', compact('tentor', 'session', 'package', 'currentInvoices', 'historyInvoices', 'paymentSummary'));
    }

    public function record(Request $request, BillInvoice $invoice, RecurringBillService $recurringBillService, TutorPackagePaymentService $paymentService): RedirectResponse
    {
        $session = $this->sessionForInvoice($request, $invoice, $paymentService);
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'other'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $notes = trim('Diterima oleh tutor '.$request->user()->name.'. '.($validated['notes'] ?? ''));
        $recurringBillService->recordPayment(
            $invoice,
            (int) $validated['amount'],
            $validated['payment_method'],
            $notes,
            $request->user(),
        );

        return redirect()->route('tutor.package-payments.show', $session)
            ->with('success', 'Penerimaan pembayaran siswa berhasil dicatat.');
    }

    private function ensureAssignedPaymentSession(Request $request, ClassSession $session, TutorPackagePaymentService $paymentService): void
    {
        abort_unless((int) $session->tentor_id === (int) $request->user()->tentorProfile?->id, 403);
        abort_unless($paymentService->packageFor($session), 404);
    }

    private function sessionForInvoice(Request $request, BillInvoice $invoice, TutorPackagePaymentService $paymentService): ClassSession
    {
        abort_unless(str_starts_with((string) $invoice->payment_scope_key, 'tutor-package:'), 404);

        $sessionId = $request->integer('class_session_id');
        $session = ClassSession::query()
            ->with(['studyGroup.package', 'schedule.packages'])
            ->whereKey($sessionId)
            ->where('study_group_id', $invoice->study_group_id)
            ->where('tentor_id', $request->user()->tentorProfile?->id)
            ->first();

        abort_unless($session && (int) $paymentService->packageFor($session)?->package_id === (int) $invoice->package_id, 403);

        return $session;
    }
}
