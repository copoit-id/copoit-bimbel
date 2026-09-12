<?php

namespace App\Services;

use App\Models\BillInvoice;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\StudyGroup;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TutorPackagePaymentService
{
    /** @return Collection<int, BillInvoice> */
    public function prepareInvoices(ClassSession $session): Collection
    {
        return DB::transaction(function () use ($session): Collection {
            $session = ClassSession::query()
                ->with(['studyGroup.package', 'studyGroup.users'])
                ->lockForUpdate()
                ->findOrFail($session->id);
            $group = StudyGroup::query()
                ->with(['package', 'users'])
                ->lockForUpdate()
                ->find($session->study_group_id);
            $package = $group?->package;

            if (! $group || ! $package || ! $this->isEnabled($package)) {
                throw ValidationException::withMessages([
                    'session' => 'Paket rombel ini belum mengaktifkan pencatatan pembayaran tutor.',
                ]);
            }

            $period = $this->periodFor($session, $package);
            $invoices = collect();

            foreach ($group->users->reject(fn ($student) => $student->pivot?->status === 'cancelled') as $student) {
                $scopeKey = $this->scopeKey($session, $package, $student->id);
                $invoices->push(BillInvoice::query()->firstOrCreate(
                    ['payment_scope_key' => $scopeKey],
                    [
                        'package_id' => $package->package_id,
                        'study_group_id' => $group->id,
                        'class_session_id' => $package->tutor_payment_frequency === 'per_session' ? $session->id : null,
                        'user_id' => $student->id,
                        'invoice_number' => $this->invoiceNumber(),
                        'title' => 'Pembayaran '.$package->name,
                        'amount' => $package->price,
                        'period_start' => $period['start'],
                        'period_end' => $period['end'],
                        'due_date' => $period['due'],
                        'status' => 'unpaid',
                        'notes' => 'Tagihan paket yang dicatat tutor untuk rombel '.$group->name.'.',
                    ]
                ));
            }

            return $invoices;
        }, 3);
    }

    public function isEnabled(?Package $package): bool
    {
        return $package !== null
            && in_array($package->tutor_payment_frequency, ['per_session', 'daily', 'monthly'], true)
            && (int) $package->price > 0;
    }

    public function scopeKey(ClassSession $session, Package $package, int $studentId): string
    {
        $period = $this->periodFor($session, $package);

        return implode(':', [
            'tutor-package',
            $package->package_id,
            $session->study_group_id,
            $studentId,
            $package->tutor_payment_frequency,
            $period['start'],
            $package->tutor_payment_frequency === 'per_session' ? $session->id : null,
        ]);
    }

    /** @return array{start: string, end: string, due: string} */
    private function periodFor(ClassSession $session, Package $package): array
    {
        $date = $session->session_date instanceof Carbon
            ? $session->session_date->copy()
            : Carbon::parse($session->session_date);

        return match ($package->tutor_payment_frequency) {
            'monthly' => [
                'start' => $date->copy()->startOfMonth()->toDateString(),
                'end' => $date->copy()->endOfMonth()->toDateString(),
                'due' => $date->copy()->endOfMonth()->toDateString(),
            ],
            default => [
                'start' => $date->toDateString(),
                'end' => $date->toDateString(),
                'due' => $date->toDateString(),
            ],
        };
    }

    private function invoiceNumber(): string
    {
        do {
            $number = 'TP-'.now()->format('Ymd').'-'.Str::upper(Str::random(7));
        } while (BillInvoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
