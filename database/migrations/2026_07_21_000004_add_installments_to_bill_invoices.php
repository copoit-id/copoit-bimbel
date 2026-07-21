<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bill_invoices')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bill_invoices MODIFY COLUMN status ENUM('unpaid', 'partial', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'unpaid'");
        }

        if (! Schema::hasTable('bill_invoice_payments')) {
            Schema::create('bill_invoice_payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('bill_invoice_id')->constrained('bill_invoices')->cascadeOnDelete();
                $table->string('receipt_number')->unique();
                $table->decimal('amount', 12, 0);
                $table->string('payment_method', 50)->default('manual');
                $table->text('notes')->nullable();
                $table->timestamp('paid_at')->index();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['bill_invoice_id', 'paid_at']);
            });
        }

        DB::table('bill_invoices')
            ->leftJoin('bill_invoice_payments', 'bill_invoice_payments.bill_invoice_id', '=', 'bill_invoices.id')
            ->where('bill_invoices.status', 'paid')
            ->whereNull('bill_invoice_payments.id')
            ->select([
                'bill_invoices.id as invoice_id',
                'bill_invoices.amount',
                'bill_invoices.paid_at',
                'bill_invoices.paid_by',
            ])
            ->orderBy('bill_invoices.id')
            ->chunkById(100, function ($invoices): void {
                $now = now();
                $payments = $invoices->map(fn (object $invoice): array => [
                    'bill_invoice_id' => $invoice->invoice_id,
                    'receipt_number' => 'BILL-LEGACY-' . $invoice->invoice_id,
                    'amount' => $invoice->amount,
                    'payment_method' => 'manual',
                    'notes' => 'Riwayat pembayaran tagihan rutin yang disinkronkan otomatis.',
                    'paid_at' => $invoice->paid_at ?? $now,
                    'paid_by' => $invoice->paid_by,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($payments !== []) {
                    DB::table('bill_invoice_payments')->insert($payments);
                }
            }, 'bill_invoices.id', 'invoice_id');
    }

    public function down(): void
    {
        if (Schema::hasTable('bill_invoice_payments')) {
            Schema::dropIfExists('bill_invoice_payments');
        }

        if (Schema::hasTable('bill_invoices') && DB::getDriverName() === 'mysql') {
            DB::table('bill_invoices')->where('status', 'partial')->update(['status' => 'unpaid']);
            DB::statement("ALTER TABLE bill_invoices MODIFY COLUMN status ENUM('unpaid', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'unpaid'");
        }
    }
};
