<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\BillInvoice;
use App\Models\StudyGroup;
use App\Services\GroupBookingService;
use App\Services\RecurringBillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupBookingController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('admin.study-groups.index', [
            'tab' => 'pengajuan',
            ...$request->only('status'),
        ]);
    }

    public function approve(StudyGroup $studyGroup, GroupBookingService $groupBookingService): RedirectResponse
    {
        $groupBookingService->approve($studyGroup);

        return back()->with('success', 'Rombel disetujui. Tagihan tiap anggota telah dibuat.');
    }

    public function recordPayment(
        Request $request,
        BillInvoice $invoice,
        RecurringBillService $billService
    ): RedirectResponse {
        abort_unless($invoice->studyGroupMember()->exists(), 404);
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
                ? 'Pembayaran anggota tercatat. Status rombel diperbarui otomatis.'
                : 'Cicilan anggota berhasil dicatat.'
        );
    }
}
