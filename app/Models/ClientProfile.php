<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ClientProfile extends Model
{
    protected $table = 'client_profile';

    protected $fillable = [
        'nama_bimbel',
        'logo',
        'favicon',
        'warna_primary',
        'warna_secondary',
        'enable_certificate_management',
        'header_primary_color',
        'sidebar_primary_color',
        'enable_utbk_types',
        'allow_video_thumbnail',
        'payment_mode',
        'payment_bank_name',
        'payment_account_number',
        'payment_account_holder',
        'payment_bank_note',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_email',
        'smtp_app_password',
        'smtp_notification_email',
    ];

    protected $casts = [
        'enable_certificate_management' => 'boolean',
        'header_primary_color' => 'boolean',
        'sidebar_primary_color' => 'boolean',
        'enable_utbk_types' => 'boolean',
        'allow_video_thumbnail' => 'boolean',
    ];

    protected function smtpAppPassword(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return $value;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return null;
                }
            },
            set: fn (?string $value): ?string => ($value === null || $value === '')
                ? null
                : Crypt::encryptString($value),
        );
    }
}
