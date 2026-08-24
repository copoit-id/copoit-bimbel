<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembelian Baru - {{ $brandName }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f6f8fb; margin:0; padding:24px; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <tr>
            <td style="padding:20px 24px; background:#1C3259; color:#ffffff;">
                <h1 style="margin:0; font-size:20px; font-weight:700;">Pembelian Baru</h1>
                <p style="margin:8px 0 0; font-size:13px; opacity:0.9;">{{ $brandName }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 16px;">Ada pembelian baru yang menunggu verifikasi.</p>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Nama Pembeli</td>
                        <td style="padding:8px 0; font-weight:600;">{{ $purchase->user->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Email Pembeli</td>
                        <td style="padding:8px 0; font-weight:600;">{{ $purchase->user->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Jenis Pembelian</td>
                        <td style="padding:8px 0; font-weight:600;">{{ $purchaseType }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Item</td>
                        <td style="padding:8px 0; font-weight:600;">{{ $itemName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Nominal</td>
                        <td style="padding:8px 0; font-weight:600;">Rp{{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Metode Pembayaran</td>
                        <td style="padding:8px 0;">{{ ucfirst($purchase->payment_method) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Waktu Pembelian</td>
                        <td style="padding:8px 0;">{{ $purchase->created_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i') ?? '-' }}</td>
                    </tr>
                </table>

                <p style="margin:24px 0 0; font-size:13px; color:#6b7280;">
                    Silakan login ke panel admin untuk memverifikasi pembelian ini.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
