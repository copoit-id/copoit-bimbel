<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DiagnoseSmtp extends Command
{
    protected $signature = 'smtp:diagnose {--to= : Send a test email to this address}';

    protected $description = 'Show dynamic SMTP configuration and optionally send a test email';

    public function handle(): int
    {
        $this->info('Dynamic mail configuration');
        $this->line('mail.default: ' . (string) config('mail.default'));
        $this->line('smtp.host: ' . (string) config('mail.mailers.smtp.host'));
        $this->line('smtp.port: ' . (string) config('mail.mailers.smtp.port'));
        $this->line('smtp.scheme: ' . (string) config('mail.mailers.smtp.scheme'));
        $this->line('smtp.encryption: ' . (string) config('mail.mailers.smtp.encryption'));
        $this->line('smtp.username: ' . $this->maskEmail((string) config('mail.mailers.smtp.username')));
        $this->line('smtp.password_set: ' . (config('mail.mailers.smtp.password') ? 'yes' : 'no'));
        $this->line('from.address: ' . $this->maskEmail((string) config('mail.from.address')));
        $this->line('client.smtp_email: ' . $this->maskEmail((string) config('client.branding.smtp_email')));

        $recipient = $this->option('to');
        if (!$recipient) {
            return self::SUCCESS;
        }

        try {
            Mail::raw('SMTP test email from ' . config('app.name') . ' at ' . now()->toDateTimeString(), function ($message) use ($recipient) {
                $message->to($recipient)->subject('SMTP Test - ' . config('app.name'));
            });

            $this->info('Test email sent successfully to ' . $this->maskEmail((string) $recipient));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Test email failed: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }

    private function maskEmail(string $email): string
    {
        if ($email === '' || !str_contains($email, '@')) {
            return $email ?: '-';
        }

        [$name, $domain] = explode('@', $email, 2);
        $visibleName = mb_substr($name, 0, 2);

        return $visibleName . str_repeat('*', max(1, mb_strlen($name) - 2)) . '@' . $domain;
    }
}
