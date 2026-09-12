<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\BillInvoice;
use App\Models\ClassSession;
use App\Services\RecurringBillService;
use App\Services\TutorPackagePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TutorPackagePaymentController extends Controller
{
    public function index(Request $request, TutorPackagePaymentService $paymentService): View
    {
        $tentor = $request->user()->tentorProfile;
        $sessions = ClassSession::query()
            ->with(['schedule:id,title', 'studyGroup:id,name,package_id', 'studyGroup.package:package_id,name,price,tutor_payment_frequency'])
            ->where('tentor_id', $tentor->id)
            ->whereNotNull('study_group_id')
            ->whereHas('studyGroup.package', fn ($query) => $query
                ->whereIn('tutor_payment_frequency', TutorPackagePaymentService::BILLING_FREQUENCIES)
                ->where('price', '>', 0))
            ->latest('session_date')
            ->paginate(20);

        return view('tutor.package-payments.index', compact('tentor', 'sessions', 'paymentService'));
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
        $this->ensureAssignedPaymentSession($request, $session, $paymentService);
        $session->load(['schedule:id,title', 'studyGroup.package:package_id,name,price,tutor_payment_frequency']);
        $package = $session->studyGroup->package;
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

        return view('tutor.package-payments.show', compact('tentor', 'session', 'package', 'currentInvoices', 'historyInvoices'));
    }

    public function record(Request $request, BillInvoice $invoice, RecurringBillService $recurringBillService): RedirectResponse
    {
        $session = $this->sessionForInvoice($request, $invoice);
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
        $session->loadMissing('studyGroup.package');
        abort_unless($paymentService->isEnabled($session->studyGroup?->package), 404);
    }

    private function sessionForInvoice(Request $request, BillInvoice $invoice): ClassSession
    {
        abort_unless(str_starts_with((string) $invoice->payment_scope_key, 'tutor-package:'), 404);

        $sessionId = $request->integer('class_session_id');
        $session = ClassSession::query()
            ->whereKey($sessionId)
            ->where('study_group_id', $invoice->study_group_id)
            ->where('tentor_id', $request->user()->tentorProfile?->id)
            ->first();

        abort_unless($session && (int) $session->studyGroup?->package_id === (int) $invoice->package_id, 403);

        return $session;
    }
}
