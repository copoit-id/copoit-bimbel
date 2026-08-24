<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi - {{ $clientBranding['name'] ?? config('client.branding.name') }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                padding: 20px !important;
            }
            .email-card {
                padding: 24px !important;
                border-radius: 8px !important;
            }
            .email-button {
                display: block !important;
                text-align: center !important;
                padding: 12px 16px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; width: 100%; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 40px 10px;">
                <!--[if mso]>
                <table role="presentation" align="center" border="0" cellspacing="0" cellpadding="0" width="560">
                <tr>
                <td>
                <![endif]-->
                <table class="email-container" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; margin: 0 auto; text-align: left;">
                    
                    <!-- Logo / Brand Section -->
                    <tr>
                        <td style="padding-bottom: 24px; padding-left: 4px;">
                            @if(!empty($clientBranding['logo_url']))
                                <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] ?? config('client.branding.name') }}" style="height: 40px; max-height: 48px; width: {{ ($clientBranding['logo_display_mode'] ?? 'square') === 'original' ? 'auto' : '40px' }}; display: block; border: 0;">
                            @else
                                <span style="font-size: 20px; font-weight: 700; color: {{ $clientBranding['primary_color'] ?? '#1c3259' }}; letter-spacing: -0.5px;">
                                    {{ $clientBranding['name'] ?? config('client.branding.name') }}
                                </span>
                            @endif
                        </td>
                    </tr>

                    <!-- Main Email Content Card -->
                    <tr>
                        <td class="email-card" style="background-color: #ffffff; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);">
                            
                            <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #0f172a; line-height: 1.4;">
                                Permintaan Atur Ulang Kata Sandi
                            </h1>
                            
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #475569;">
                                Halo {{ $user->safe_name_for_email }},
                            </p>
                            
                            <p style="margin: 0 0 24px 0; font-size: 15px; line-height: 1.6; color: #475569;">
                                Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda di <strong>{{ $clientBranding['name'] ?? config('client.branding.name') }}</strong>. Silakan klik tombol di bawah ini untuk melanjutkan:
                            </p>
                            
                            <!-- Call to Action Button -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
                                <tr>
                                    <td>
                                        <a class="email-button" href="{{ $resetUrl }}" target="_blank" style="background-color: {{ $clientBranding['primary_color'] ?? '#1c3259' }}; color: #ffffff; display: inline-block; padding: 12px 28px; font-size: 15px; font-weight: 600; text-decoration: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); transition: background-color 0.2s ease;">
                                            Atur Ulang Kata Sandi
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Important Security Notice -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-left: 4px solid {{ $clientBranding['primary_color'] ?? '#1c3259' }}; border-radius: 4px; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 16px; font-size: 13px; line-height: 1.5; color: #64748b;">
                                        <div style="font-weight: 700; color: #475569; margin-bottom: 6px;">Catatan Keamanan Penting:</div>
                                        <ul style="margin: 0; padding-left: 20px;">
                                            <li style="margin-bottom: 4px;">Tautan ini hanya berlaku selama <strong>1 jam</strong>.</li>
                                            <li style="margin-bottom: 4px;">Jika Anda tidak meminta atur ulang kata sandi ini, Anda dapat mengabaikan email ini dengan aman.</li>
                                            <li>Jangan pernah membagikan tautan ini kepada siapapun demi keamanan akun Anda.</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>

                            <!-- Troubleshoot / Raw Link Section -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top: 1px solid #e2e8f0; padding-top: 24px;">
                                <tr>
                                    <td style="font-size: 12px; line-height: 1.5; color: #94a3b8;">
                                        Jika tombol di atas tidak berfungsi, salin dan tempel tautan berikut ke browser Anda:
                                        <div style="margin-top: 8px; word-break: break-all;">
                                            <a href="{{ $resetUrl }}" target="_blank" style="color: {{ $clientBranding['primary_color'] ?? '#1c3259' }}; text-decoration: underline;">
                                                {{ $resetUrl }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer Section -->
                    <tr>
                        <td style="padding: 32px 0 0 0; text-align: center; font-size: 12px; line-height: 1.6; color: #94a3b8;">
                            <p style="margin: 0 0 8px 0;">
                                Email ini dikirim secara otomatis oleh sistem {{ $clientBranding['name'] ?? config('client.branding.name') }}.
                            </p>
                            <p style="margin: 0;">
                                &copy; {{ date('Y') }} {{ $clientBranding['name'] ?? config('client.branding.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!--[if mso]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
