<?php

namespace App\Services;

use App\Models\BillInvoice;
use App\Models\RecurringBill;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecurringBillService
{
    public function generateInvoices(RecurringBill $bill, ?Carbon $until = null): int
    {
        $bill->loadMissing(['targets.user', 'targets.class.packages']);
        $until ??= now()->addMonth();
        $created = 0;

        foreach ($this->periods($bill, $until) as $period) {
            foreach ($this->targetUsers($bill) as $userId) {
                $exists = BillInvoice::query()
                    ->where('recurring_bill_id', $bill->id)
                    ->where('user_id', $userId)
                    ->whereDate('period_start', $period['start'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                BillInvoice::create([
                    'recurring_bill_id' => $bill->id,
                    'user_id' => $userId,
                    'invoice_number' => $this->invoiceNumber($bill->id),
                    'title' => $bill->name,
                    'amount' => $bill->amount,
                    'period_start' => $period['start'],
                    'period_end' => $period['end'],
                    'due_date' => $period['due'],
                    'status' => 'unpaid',
                ]);

                $created++;
            }
        }

        return $created;
    }

    public function markOverdue(): int
    {
        return BillInvoice::query()
            ->where('status', 'unpaid')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }

    private function targetUsers(RecurringBill $bill): Collection
    {
        $userIds = collect();

        foreach ($bill->targets as $target) {
            if ($target->user_id) {
                $userIds->push((int) $target->user_id);
                continue;
            }

            if ($target->class_id) {
                $packageIds = $target->class?->packages()->pluck('packages.package_id') ?? collect();

                $classUserIds = UserPackageAcces::query()
                    ->whereIn('package_id', $packageIds)
                    ->active()
                    ->pluck('user_id');

                $userIds = $userIds->merge($classUserIds);
            }
        }

        return $userIds->filter()->unique()->values();
    }

    private function periods(RecurringBill $bill, Carbon $until): array
    {
        $periods = [];
        $cursor = $bill->start_date->copy()->startOfDay();
        $endLimit = $bill->end_date ? $bill->end_date->copy()->endOfDay()->min($until) : $until;

        while ($cursor->lte($endLimit)) {
            $periodEnd = match ($bill->frequency) {
                'daily' => $cursor->copy()->endOfDay(),
                'weekly' => $cursor->copy()->endOfWeek(),
                'monthly' => $cursor->copy()->endOfMonth(),
                'yearly' => $cursor->copy()->endOfYear(),
            };

            $dueDate = $this->dueDate($bill, $cursor, $periodEnd);
            $periods[] = [
                'start' => $cursor->toDateString(),
                'end' => $periodEnd->toDateString(),
                'due' => $dueDate->toDateString(),
            ];

            $cursor = match ($bill->frequency) {
                'daily' => $cursor->addDay(),
                'weekly' => $cursor->addWeek()->startOfWeek(),
                'monthly' => $cursor->addMonthNoOverflow()->startOfMonth(),
                'yearly' => $cursor->addYearNoOverflow()->startOfYear(),
            };
        }

        return $periods;
    }

    private function dueDate(RecurringBill $bill, Carbon $periodStart, Carbon $periodEnd): Carbon
    {
        if (!$bill->due_day || !in_array($bill->frequency, ['monthly', 'yearly'], true)) {
            return $periodEnd->copy();
        }

        return $periodStart->copy()->day(min((int) $bill->due_day, $periodStart->daysInMonth));
    }

    private function invoiceNumber(int $billId): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . $billId . '-' . Str::upper(Str::random(6));
    }
}
