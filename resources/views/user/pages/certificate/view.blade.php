<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $certificate->certificate_name ?? 'Sertifikat' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .certificate-container {
            background: white;
            width: 1200px;
            height: 850px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        .certificate-border {
            position: absolute;
            top: 30px;
            left: 30px;
            right: 30px;
            bottom: 30px;
            border: 4px solid #2563eb;
            border-radius: 8px;
        }

        .certificate-inner-border {
            position: absolute;
            top: 50px;
            left: 50px;
            right: 50px;
            bottom: 50px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
        }

        .certificate-header {
            position: absolute;
            top: 80px;
            left: 80px;
            right: 80px;
            height: 80px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            font-size: 14px;
            color: #2c3e50;
        }

        .certificate-main {
            position: absolute;
            top: 200px;
            left: 80px;
            right: 80px;
            text-align: center;
        }

        .certificate-title {
            font-size: 36px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 15px;
        }

        .certificate-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 40px;
        }

        .certificate-text {
            font-size: 18px;
            color: #374151;
            margin-bottom: 20px;
        }

        .certificate-name {
            font-size: 32px;
            font-weight: bold;
            color: #1a365d;
            margin: 20px 0;
            text-transform: uppercase;
        }

        .certificate-scores {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin: 40px 0;
        }

        .score-item {
            text-align: center;
        }

        .score-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .score-value {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
        }

        .certificate-overall-score {
            font-size: 20px;
            font-weight: bold;
            color: #059669;
            margin: 30px 0;
        }

        .certificate-footer {
            position: absolute;
            bottom: 80px;
            left: 80px;
            right: 80px;
            display: flex;
            justify-content: space-between;
            align-items: end;
            font-size: 12px;
            color: #6b7280;
        }

        .footer-left,
        .footer-right {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .footer-right {
            text-align: right;
        }

        .institution-name {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .certificate-container {
                box-shadow: none;
                width: 100%;
                height: 100vh;
            }
        }
    </style>
</head>

<body>
    <div class="certificate-container">
        <!-- Certificate Border -->
        <div class="certificate-border"></div>
        <div class="certificate-inner-border"></div>

        <!-- Certificate Header -->
        <div class="certificate-header">
            <div>
                <div>Certificate No: {{ $certificate->certificate_number }}</div>
            </div>
            <div style="text-align: right;">
                <div>Date of Birth: {{ $certificate->date_of_birth instanceof \Carbon\Carbon ?
                    $certificate->date_of_birth->format('d F Y') :
                    \Carbon\Carbon::parse($certificate->date_of_birth)->format('d F Y') }}</div>
            </div>
        </div>

        <!-- Certificate Main Content -->
        <div class="certificate-main">
            <div class="certificate-title">CERTIFICATE OF ACHIEVEMENT</div>
            <div class="certificate-subtitle">Test of English as a Foreign Language - Institutional Testing Program
            </div>

            <div class="certificate-text">This is to certify that</div>

            @php
            $metadata = is_array($certificate->metadata) ? $certificate->metadata : json_decode($certificate->metadata,
            true);
            $userName = $metadata['user_name'] ?? 'Unknown User';
            $examDate = isset($metadata['exam_date']) ? \Carbon\Carbon::parse($metadata['exam_date']) :
            $certificate->issued_date;
            $overallScore = $metadata['score'] ?? 0;
            @endphp

            <div class="certificate-name">{{ $userName }}</div>

            <div class="certificate-text">has successfully completed the TOEFL ITP test</div>

            <!-- Subtest Scores -->
            @if(isset($metadata['subtest_details']) && is_array($metadata['subtest_details']))
            <div class="certificate-scores">
                @foreach($metadata['subtest_details'] as $subtest)
                @if($loop->index < 3 && is_array($subtest) && isset($subtest['name']) &&
                    isset($subtest['display_score'])) <div class="score-item">
                    <div class="score-label">{{ $subtest['name'] }}</div>
                    <div class="score-value">{{ $subtest['display_score'] }}</div>
            </div>
            @endif
            @endforeach
        </div>
        @endif

        <div class="certificate-overall-score">Overall Score: {{ round($overallScore, 1) }}%</div>

        <div style="margin-top: 40px; font-size: 14px; color: #6b7280;">
            Exam Date: {{ $examDate->format('F d, Y') }}
        </div>
    </div>

    <!-- Certificate Footer -->
    <div class="certificate-footer">
        <div class="footer-left">
            <div>Issued: {{ $certificate->issued_date->format('F d, Y') }}</div>
            <div>Verification: {{ substr($certificate->verification_code, 0, 8) }}</div>
        </div>
        <div class="footer-right">
            <div class="institution-name">CPNS Academy</div>
            <div>Director</div>
        </div>
    </div>
    </div>
</body>

</html>
