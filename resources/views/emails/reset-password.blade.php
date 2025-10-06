<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CPNS Academy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }

        .logo {
            background-color: #2563eb;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .title {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }

        .content {
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }

        .btn:hover {
            background-color: #1d4ed8;
        }

        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .warning {
            background-color: #fef3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">🎓</div>
            <h1 class="title">CPNS Academy</h1>
        </div>

        <div class="content">
            <h2>Halo, {{ $user->name }}!</h2>

            <p>Kami menerima permintaan untuk reset password akun Anda di CPNS Academy.</p>

            <p>Jika Anda meminta reset password, klik tombol di bawah ini untuk melanjutkan:</p>

            <div style="text-align: center; color: white">
                <a href="{{ $resetUrl }}" class="btn">Reset Password Saya</a>
            </div>

            <div class="warning">
                <strong>⚠️ Penting:</strong>
                <ul>
                    <li>Link ini akan kadaluarsa dalam 1 jam</li>
                    <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
                    <li>Jangan bagikan link ini kepada siapa pun</li>
                </ul>
            </div>

            <p>Jika Anda mengalami kesulitan dengan tombol di atas, hubungi tim support kami.</p>
        </div>

        <div class="footer">
            <p>Email ini dikirim otomatis oleh sistem CPNS Academy.</p>
            <p>© {{ date('Y') }} CPNS Academy. All rights reserved.</p>
        </div>
    </div>
</body>

</html>