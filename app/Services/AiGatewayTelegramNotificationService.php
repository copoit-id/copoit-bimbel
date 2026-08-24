<?php

namespace App\Services;

use App\Models\AiGatewayTransaction;
use App\Models\ClientProfile;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class AiGatewayTelegramNotificationService
{
    public function notifyPurchase(int $transactionId): void
    {
        $settings = $this->settings();
        if (! $this->isConfigured($settings)) {
            return;
        }

        $transaction = AiGatewayTransaction::query()
            ->with(['client:id,name', 'plan:id,name,price,token_limit,chat_limit,duration_days', 'subscription'])
            ->find($transactionId);
        if (! $transaction || $transaction->status !== 'paid') {
            return;
        }

        $notificationType = $transaction->amount === 0 ? 'free' : 'paid';
        if (! (bool) ($settings["notify_{$notificationType}"] ?? true)) {
            return;
        }

        if (! $this->reserve($transaction)) {
            return;
        }

        try {
            $this->send($settings, $this->purchaseMessage($transaction));
            $this->recordResult($transaction->id, 'sent');
        } catch (Throwable $exception) {
            $this->recordResult($transaction->id, 'failed');
            Log::warning('Notifikasi Telegram pembelian AI gagal dikirim.', [
                'ai_gateway_transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function sendTest(): void
    {
        $settings = $this->settings();
        if (! $this->isConfigured($settings)) {
            throw new RuntimeException('Aktifkan Telegram dan simpan Bot Token serta Chat ID terlebih dahulu.');
        }

        $this->send($settings, implode("\n", [
            '✅ <b>Tes Notifikasi AI Learning Berhasil</b>',
            '',
            'Bot Telegram sudah terhubung ke BIMBELHUB.',
            'Waktu: '.$this->escape(now()->translatedFormat('d M Y, H:i')).' WIB',
        ]));
    }

    private function settings(): array
    {
        if (! Schema::hasTable('client_profile')
            || ! Schema::hasColumn('client_profile', 'ai_gateway_telegram_settings')) {
            return [];
        }

        $settings = ClientProfile::query()->first()?->ai_gateway_telegram_settings;

        return is_array($settings) ? $settings : [];
    }

    private function isConfigured(array $settings): bool
    {
        return (bool) ($settings['enabled'] ?? false)
            && filled($settings['bot_token'] ?? null)
            && filled($settings['chat_id'] ?? null);
    }

    private function reserve(AiGatewayTransaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction): bool {
            $locked = AiGatewayTransaction::query()->lockForUpdate()->find($transaction->id);
            if (! $locked || $locked->status !== 'paid') {
                return false;
            }

            $details = is_array($locked->details) ? $locked->details : [];
            if (filled(data_get($details, 'telegram_notification.sent_at'))
                || filled(data_get($details, 'telegram_notification.reserved_at'))) {
                return false;
            }

            data_set($details, 'telegram_notification', [
                'status' => 'sending',
                'reserved_at' => now()->toIso8601String(),
            ]);
            $locked->update(['details' => $details]);

            return true;
        });
    }

    private function recordResult(int $transactionId, string $status): void
    {
        DB::transaction(function () use ($transactionId, $status): void {
            $transaction = AiGatewayTransaction::query()->lockForUpdate()->find($transactionId);
            if (! $transaction) {
                return;
            }

            $details = is_array($transaction->details) ? $transaction->details : [];
            data_set($details, 'telegram_notification', [
                'status' => $status,
                "{$status}_at" => now()->toIso8601String(),
            ]);
            $transaction->update(['details' => $details]);
        });
    }

    private function send(array $settings, string $message): void
    {
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->post('https://api.telegram.org/bot'.$settings['bot_token'].'/sendMessage', array_filter([
                    'chat_id' => $settings['chat_id'],
                    'message_thread_id' => $settings['message_thread_id'] ?? null,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ], fn ($value): bool => $value !== null && $value !== ''));
        } catch (Throwable $exception) {
            throw new RuntimeException('Telegram tidak dapat dihubungi.', previous: $exception);
        }

        if (! $this->wasSuccessful($response)) {
            $description = trim(strip_tags((string) $response->json('description')));
            throw new RuntimeException($description !== ''
                ? 'Telegram menolak pesan: '.$description
                : 'Telegram menolak pesan notifikasi.');
        }
    }

    private function wasSuccessful(Response $response): bool
    {
        return $response->successful() && $response->json('ok') === true;
    }

    private function purchaseMessage(AiGatewayTransaction $transaction): string
    {
        $subscription = $transaction->subscription;
        $plan = $transaction->plan;
        $isFree = $transaction->amount === 0;
        $duration = (int) ($plan?->duration_days ?? 0);

        return implode("\n", [
            ($isFree ? '🎁' : '💰').' <b>Paket AI Learning '.($isFree ? 'Gratis Diklaim' : 'Berhasil Dibayar').'</b>',
            '',
            '<b>Project:</b> '.$this->escape($transaction->client?->name ?? '-'),
            '<b>Pengguna:</b> '.$this->escape($subscription?->external_user_name ?: '-'),
            '<b>Email:</b> '.$this->escape($subscription?->external_user_email ?: '-'),
            '<b>User ID:</b> <code>'.$this->escape($subscription?->external_user_id ?: '-').'</code>',
            '',
            '<b>Paket:</b> '.$this->escape($plan?->name ?? '-'),
            '<b>Jenis:</b> '.($isFree ? 'Gratis' : 'Berbayar'),
            '<b>Nominal:</b> Rp '.number_format((int) $transaction->amount, 0, ',', '.'),
            '<b>Kuota:</b> '.number_format((int) ($plan?->token_limit ?? 0), 0, ',', '.').' token',
            '<b>Masa aktif:</b> '.($duration === 0 ? 'Selamanya' : $duration.' hari'),
            '<b>Provider:</b> '.$this->escape($transaction->provider),
            '<b>Transaksi:</b> <code>'.$this->escape($transaction->external_id).'</code>',
            '<b>Waktu:</b> '.$this->escape(($transaction->paid_at ?? now())->translatedFormat('d M Y, H:i')).' WIB',
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
