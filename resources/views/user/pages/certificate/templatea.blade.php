<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Template</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .certificate-container {
            width: 800px;
            height: 600px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0 auto;
            position: relative;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .certificate-header {
            position: absolute;
            top: 20px;
            left: 30px;
            right: 30px;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .header-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            font-size: 12px;
            color: #333;
        }

        .header-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #eee;
        }

        .header-label {
            font-weight: bold;
            color: #555;
        }

        .header-value {
            color: #333;
            font-weight: 500;
        }

        .certificate-main {
            position: absolute;
            top: 140px;
            left: 30px;
            right: 30px;
            bottom: 40px;
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.05);
        }

        .certificate-title {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .certificate-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 25px;
        }

        .certificate-recipient {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .certificate-achievement {
            font-size: 14px;
            color: #374151;
            margin: 15px 0;
            line-height: 1.4;
        }

        .certificate-score {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
            margin: 20px 0;
        }

        .certificate-footer {
            position: absolute;
            bottom: 20px;
            left: 30px;
            right: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #6b7280;
            font-size: 11px;
        }

        .institution {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
        }

        .decorative-border {
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            bottom: 15px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            pointer-events: none;
        }

        .scores-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }

        .score-item {
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .score-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .score-value {
            font-size: 14px;
            font-weight: bold;
            color: #059669;
        }
    </style>
</head>

<body>
    <div class="certificate-container">
        <div class="decorative-border"></div>

        <!-- Certificate Header with Details -->
        <div class="certificate-header">
            <div class="header-grid">
                <div>
                    <div class="header-item">
                        <span class="header-label">No. Sertifikat:</span>
                        <span class="header-value">{{ $certificate->certificate_number ?? 'CERT-12345/CA/08/2025'
                            }}</span>
                    </div>
                    <div class="header-item">
                        <span class="header-label">Nama Siswa:</span>
                        <span class="header-value">{{ $userName ?? 'JOHN DOE' }}</span>
                    </div>
                    <div class="header-item">
                        <span class="header-label">Tanggal Lahir:</span>
                        <span class="header-value">{{ isset($certificate) && $certificate->date_of_birth ?
                            $certificate->date_of_birth->format('d F Y') : '1 Januari 1990' }}</span>
                    </div>
                </div>
                <div>
                    <div class="header-item">
                        <span class="header-label">Tanggal Ujian:</span>
                        <span class="header-value">{{ isset($examDate) ? $examDate->format('d F Y') : '6 Agustus 2025'
                            }}</span>
                    </div>
                    <div class="header-item">
                        <span class="header-label">Lembaga:</span>
                        <span class="header-value">CPNS Academy</span>
                    </div>
                    <div class="header-item">
                        <span class="header-label">Status:</span>
                        <span class="header-value">LULUS</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Certificate Content -->
        <div class="certificate-main">
            <div class="certificate-title">Certificate of Achievement</div>
            <div class="certificate-subtitle">Test of English as a Foreign Language - Institutional Testing Program
            </div>

            <div class="certificate-achievement">This is to certify that</div>
            <div class="certificate-recipient">{{ $userName ?? 'SAMPLE USER NAME' }}</div>
            <div class="certificate-achievement">has successfully completed the TOEFL ITP test</div>

            <!-- Detailed Scores -->
            @if(isset($subtestScores) && count($subtestScores) > 0)
            <div class="scores-grid">
                @foreach($subtestScores as $subtest)
                <div class="score-item">
                    <div class="score-label">{{ $subtest['name'] }}</div>
                    <div class="score-value">{{ $subtest['score'] }}</div>
                </div>
                @endforeach
            </div>
            @else
            <div class="scores-grid">
                <div class="score-item">
                    <div class="score-label">Writing</div>
                    <div class="score-value">88</div>
                </div>
                <div class="score-item">
                    <div class="score-label">Reading</div>
                    <div class="score-value">85</div>
                </div>
                <div class="score-item">
                    <div class="score-label">Listening</div>
                    <div class="score-value">83</div>
                </div>
            </div>
            @endif

            <div class="certificate-score">Overall Score: {{ isset($overallScore) ? round($overallScore, 0) : 85 }}%
            </div>
        </div>

        <!-- Certificate Footer -->
        <div class="certificate-footer">
            <div>
                <div>Date: {{ isset($certificate) && $certificate->issued_date ? $certificate->issued_date->format('F d,
                    Y') : 'August 6, 2025' }}
                </div>
                <div>Verification Code: {{ isset($certificate) && $certificate->verification_code ?
                    substr($certificate->verification_code, 0, 8) : 'ABC12345' }}</div>
            </div>
            <div class="institution">
                <div>CPNS Academy</div>
                <div style="font-size: 11px; font-weight: normal;">Director</div>
            </div>
        </div>
    </div>
</body>

</html>