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
    public const BILLING_FREQUENCIES = ['once', 'per_session', 'daily', 'weekly', 'monthly', 'quarterly', 'semiannual', 'yearly'];

    /** @return Collection<int, BillInvoice> */
    public function prepareInvoices(ClassSession $session): Collection
    {
        return DB::transaction(function () use ($session): Collection {
            $session = ClassSession::query()
                ->with(['studyGroup.package', 'studyGroup.users', 'schedule.packages'])
                ->lockForUpdate()
                ->findOrFail($session->id);
            $group = StudyGroup::query()
                ->with(['package', 'users'])
                ->lockForUpdate()
                ->find($session->study_group_id);
            $package = $this->packageFor($session);

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
                        'billing_source' => BillInvoice::SOURCE_SCHEDULE,
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

    /**
     * Buat tagihan untuk sesi yang sudah tiba tanpa menunggu tindakan tutor.
     * Unique payment_scope_key menjaga proses scheduler aman saat dijalankan ulang.
     */
    public function prepareDueInvoices(): int
    {
        $prepared = 0;

        ClassSession::query()
            ->with(['studyGroup.package', 'schedule.packages'])
            ->whereNotNull('study_group_id')
            ->whereDate('session_date', '<=', today()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->chunkById(50, function (Collection $sessions) use (&$prepared): void {
                foreach ($sessions as $session) {
                    if (! $this->isEnabled($this->packageFor($session))) {
                        continue;
                    }

                    $this->prepareInvoices($session);
                    $prepared++;
                }
            });

        return $prepared;
    }

    public function isEnabled(?Package $package): bool
    {
        return $package !== null
            && in_array($package->tutor_payment_frequency, self::BILLING_FREQUENCIES, true)
            && (int) $package->price > 0;
    }

    /**
     * Resolve the package used for a rombel session payment.
     *
     * A package explicitly owned by the rombel has priority. For regular
     * rombel, a single eligible program package assigned to its schedule is
     * used instead. Multiple eligible schedule packages remain ambiguous and
     * deliberately require the rombel to be associated with a package.
     */
    public function packageFor(ClassSession $session): ?Package
    {
        $session->loadMissing(['studyGroup.package', 'schedule.packages']);

        if ($this->isEnabled($session->studyGroup?->package)) {
            return $session->studyGroup->package;
        }

        $schedulePackages = $session->schedule?->packages
            ?->filter(fn (Package $package): bool => $this->isEnabled($package))
            ->values() ?? collect();

        return $schedulePackages->count() === 1 ? $schedulePackages->first() : null;
    }

    public static function billingFrequencyLabel(?string $frequency, bool $short = false): string
    {
        return match ($frequency) {
            'once' => 'Sekali tagih',
            'per_session' => $short ? 'pertemuan' : 'Setiap pertemuan',
            'daily' => $short ? 'hari' : 'Harian',
            'weekly' => $short ? 'minggu' : 'Mingguan',
            'monthly' => $short ? 'bulan' : 'Bulanan',
            'quarterly' => $short ? '3 bulan' : 'Setiap 3 bulan',
            'semiannual' => $short ? '6 bulan' : 'Setiap 6 bulan',
            'yearly' => $short ? 'tahun' : 'Setiap 1 tahun',
            default => '—',
        };
    }

    public function scopeKey(ClassSession $session, Package $package, int $studentId): string
    {
        $period = $this->periodFor($session, $package);

        $periodKey = $package->tutor_payment_frequency === 'once'
            ? 'once'
            : $period['start'];

        return implode(':', [
            'tutor-package',
            $package->package_id,
            $session->study_group_id,
            $studentId,
            $package->tutor_payment_frequency,
            $periodKey,
            $package->tutor_payment_frequency === 'per_session' ? $session->id : null,
        ]);
    }

    public function periodStart(ClassSession $session, Package $package): ?string
    {
        return $package->tutor_payment_frequency === 'once'
            ? null
            : $this->periodFor($session, $package)['start'];
    }

    /** @return array{start: string, end: string, due: string} */
    private function periodFor(ClassSession $session, Package $package): array
    {
        $date = $session->session_date instanceof Carbon
            ? $session->session_date->copy()
            : Carbon::parse($session->session_date);

        return match ($package->tutor_payment_frequency) {
            'weekly' => [
                'start' => $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'end' => $date->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                'due' => $date->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ],
            'monthly' => [
                'start' => $date->copy()->startOfMonth()->toDateString(),
                'end' => $date->copy()->endOfMonth()->toDateString(),
                'due' => $date->copy()->endOfMonth()->toDateString(),
            ],
            'quarterly' => [
                'start' => $date->copy()->startOfQuarter()->toDateString(),
                'end' => $date->copy()->endOfQuarter()->toDateString(),
                'due' => $date->copy()->endOfQuarter()->toDateString(),
            ],
            'semiannual' => [
                'start' => $date->copy()->month($date->month <= 6 ? 1 : 7)->startOfMonth()->toDateString(),
                'end' => $date->copy()->month($date->month <= 6 ? 6 : 12)->endOfMonth()->toDateString(),
                'due' => $date->copy()->month($date->month <= 6 ? 6 : 12)->endOfMonth()->toDateString(),
            ],
            'yearly' => [
                'start' => $date->copy()->startOfYear()->toDateString(),
                'end' => $date->copy()->endOfYear()->toDateString(),
                'due' => $date->copy()->endOfYear()->toDateString(),
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
