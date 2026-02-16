<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftar Baru - {{ $brandName }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f6f8fb; margin:0; padding:24px; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <tr>
            <td style="padding:20px 24px; background:#1C3259; color:#ffffff;">
                <h1 style="margin:0; font-size:20px; font-weight:700;">Pendaftar Baru</h1>
                <p style="margin:8px 0 0; font-size:13px; opacity:0.9;">{{ $brandName }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 16px;">Ada pendaftaran user baru di platform.</p>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Nama</td>
                        <td style="padding:8px 0; font-weight:600;">{{ $newUser->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Email</td>
                        <td style="padding:8px 0; font-weight:600;">{{ $newUser->email }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Tanggal Lahir</td>
                        <td style="padding:8px 0;">{{ optional($newUser->date_of_birth)->format('d-m-Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; width:160px; color:#6b7280;">Waktu Daftar</td>
                        <td style="padding:8px 0;">{{ $registeredAt }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
