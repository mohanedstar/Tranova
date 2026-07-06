<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificate of Internship Completion</title>
    <style>
        /* ✅ خطوط Google للغة الإنجليزية */
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@400;600;700&display=swap');

        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            /* ✅ DejaVu Sans يدعم العربية ومدمج في DomPDF */
            font-family: 'DejaVu Sans', 'Montserrat', 'Playfair Display', sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #1a1a1a;
        }

        .certificate-container {
            width: 100%;
            height: 100%;
            padding: 40px;
            box-sizing: border-box;
            position: relative;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        }

        .border-frame {
            border: 8px solid #1e3a5f;
            padding: 8px;
            height: calc(100% - 96px);
            box-sizing: border-box;
            position: relative;
        }

        .inner-border {
            border: 2px solid #c9a961;
            padding: 30px;
            height: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 2px;
        }

        .certificate-title {
            font-size: 42px;
            font-weight: 700;
            color: #c9a961;
            margin: 20px 0;
            font-family: 'Playfair Display', serif;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .subtitle {
            font-size: 18px;
            color: #555;
            margin-bottom: 30px;
            font-style: italic;
        }

        .content {
            text-align: center;
            flex-grow: 1;
        }

        .presented-to {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ✅ اسم الطالب باللون الأسود */
        .student-name {
            font-size: 36px;
            font-weight: 700;
            color: #000000; /* ✅ تم التغيير من #1e3a5f إلى #000000 */
            margin: 15px 0;
            font-family: 'DejaVu Sans', 'Playfair Display', serif;
            border-bottom: 2px solid #c9a961;
            display: inline-block;
            padding: 0 30px 10px;
        }

        /* ✅ معلومات الطالب - تدعم العربية */
    /* ✅ معلومات الطالب - باللون الأسود */
.student-info {
    font-size: 14px;
    color: #000000;  /* ✅ أسود تماماً */
    margin: 10px 0;
    font-style: normal;  /* ✅ إزالة italic ليكون أوضح */
    font-family: 'DejaVu Sans', sans-serif;
    direction: ltr;
    font-weight: 500;  /* ✅ سمك متوسط للوضوح */
}

        .description {
            font-size: 16px;
            line-height: 1.8;
            color: #444;
            margin: 20px auto;
            max-width: 80%;
            font-family: 'DejaVu Sans', sans-serif;
        }

        .opportunity-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e3a5f;
            margin: 15px 0;
            font-family: 'DejaVu Sans', 'Playfair Display', serif;
            font-style: italic;
        }

        .grade-badge {
            display: inline-block;
            background: #c9a961;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 700;
            margin: 15px 0;
            letter-spacing: 1px;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
            padding-top: 20px;
        }

        .signature-block {
            text-align: center;
            width: 30%;
        }

        .signature-line {
            border-top: 2px solid #1e3a5f;
            padding-top: 8px;
            margin-top: 40px;
            font-size: 14px;
            color: #333;
        }

        /* ✅ أسماء الموقعين باللون الأسود */
        .signature-name {
            font-weight: 700;
            color: #000000; /* ✅ تم التغيير */
            font-size: 15px;
            font-family: 'DejaVu Sans', sans-serif;
        }

        .signature-title {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .certificate-number {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-top: 15px;
        }

        .qr-placeholder {
            width: 80px;
            height: 80px;
            border: 1px dashed #ccc;
            display: inline-block;
            margin: 0 auto;
            text-align: center;
            line-height: 80px;
            font-size: 10px;
            color: #999;
        }

        .date-info {
            font-size: 14px;
            color: #555;
            margin-top: 10px;
            font-family: 'DejaVu Sans', sans-serif;
        }

        .seal {
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 100px;
            border: 3px solid #c9a961;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c9a961;
            font-weight: 700;
            font-size: 12px;
            text-align: center;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="border-frame">
            <div class="inner-border">
                <!-- Header -->
                <div class="header">
                    <div class="logo">TRINOVA PLATFORM</div>
                    <div class="certificate-title">Certificate of Internship Completion</div>
                    <div class="subtitle">This certificate is proudly presented to</div>
                </div>

                <!-- Content -->
                <div class="content">
                    <div class="presented-to">This is to certify that</div>

                    <!-- ✅ اسم الطالب باللون الأسود -->
                    <div class="student-name">{{ $studentName }}</div>

                    <!-- ✅ معلومات الطالب - تدعم العربية -->
                    <div class="student-info">
                        Student ID: {{ $studentId }} | Major: {{ $studentMajor }} | University: {{ $studentUniversity }}
                    </div>

                    <div class="description">
                        has successfully completed the internship program in
                        <div class="opportunity-title">"{{ $opportunityTitle }}"</div>
                        at {{ $providerName }}
                        <br>
                        during the period from {{ $startDate }} to {{ $endDate }}
                        <br>
                        with a total of {{ $totalHours }} training hours
                    </div>

                    <div class="grade-badge">
                        Final Grade: {{ $finalGrade }} / 100 - {{ $gradeStatus }}
                    </div>
                </div>

                <!-- Footer with Signatures -->
                <div class="footer">
                    <div class="signature-block">
                        <div class="signature-line">
                            <div class="signature-name">{{ $providerName }}</div>
                            <div class="signature-title">Training Provider</div>
                        </div>
                    </div>

                    <div class="signature-block">
                        <div class="qr-placeholder">QR Code</div>
                        <div class="date-info">
                            Certificate No: {{ $certificateNumber }}
                            <br>
                            Issue Date: {{ $issueDateFormatted }}
                        </div>
                    </div>

                    <div class="signature-block">
                        <div class="signature-line">
                            <div class="signature-name">{{ $supervisorName }}</div>
                            <div class="signature-title">Academic Supervisor</div>
                        </div>
                    </div>
                </div>

                <div class="certificate-number">
                    This certificate is issued electronically by Trinova Platform and can be verified through the official website
                </div>
            </div>
        </div>
    </div>
</body>
</html>
