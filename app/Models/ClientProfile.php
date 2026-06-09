<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'payment_unique_code_enabled',
        'payment_gateway',
        'payment_gateway_mode',
        'xendit_secret_key',
        'xendit_webhook_token',
        'midtrans_server_key',
        'midtrans_client_key',
        'interactive_qris_api_key',
        'interactive_qris_mid',
        'interactive_qris_use_tip',
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
        'payment_unique_code_enabled' => 'boolean',
        'smtp_app_password' => 'encrypted',
        'xendit_secret_key' => 'encrypted',
        'xendit_webhook_token' => 'encrypted',
        'midtrans_server_key' => 'encrypted',
        'midtrans_client_key' => 'encrypted',
        'interactive_qris_api_key' => 'encrypted',
        'interactive_qris_use_tip' => 'boolean',
    ];
}
