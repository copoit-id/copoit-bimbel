<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\BillInvoice;
use App\Models\BookingCohort;
use App\Services\RecurringBillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupBookingController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $status = in_array($status, ['forming', 'ready', 'cancelled', 'expired'], true)
            ? $status
            : null;
        $cohorts = BookingCohort::query()
            ->with([
                'package:package_id,name',
                'organizer:id,name,email',
                'studyGroup:id,name,tentor_id',
                'participants.user:id,name,email,phone',
                'participants.invoice.payments',
            ])
            ->withCount([
                'participants',
                'participants as paid_participants_count' => fn ($query) => $query->where('status', 'paid'),
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.pages.package.booking.cohorts', compact('cohorts', 'status'));
    }

    public function recordPayment(
        Request $request,
        BillInvoice $invoice,
        RecurringBillService $billService
    ): RedirectResponse {
        abort_unless($invoice->cohortParticipant()->exists(), 404);
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
            $request->user()
        );

        return back()->with(
            'success',
            $invoice->status === 'paid'
                ? 'Pembayaran anggota tercatat. Status kelompok diperbarui otomatis.'
                : 'Cicilan anggota berhasil dicatat.'
        );
    }
}
