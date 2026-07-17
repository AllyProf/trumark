<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8f9fa; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #4a4a4a; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 1px solid #f1f1f1; }
        .logo { max-width: 180px; height: auto; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .greeting { font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 20px; }
        .body-text { font-size: 16px; margin-bottom: 30px; color: #5a5a5a; }
        .cta-container { text-align: center; margin: 40px 0; }
        .btn { background-color: #940000; color: #ffffff !important; padding: 15px 35px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block; transition: background-color 0.3s; }
        .footer { background-color: #f1f1f1; padding: 30px; text-align: center; font-size: 13px; color: #888888; }
        .social-links { margin-bottom: 15px; }
        .company-info { margin-top: 10px; }
        @media screen and (max-width: 600px) {
            .main { width: 95% !important; }
            .content { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main" width="100%">
            <tr>
                <td class="header">
                    <img src="{{ $message->embed(public_path('assets/images/trumark-logo.jpeg')) }}" alt="TruMark Logo" class="logo">
                </td>
            </tr>
            <tr>
                <td class="content">
                    <div class="greeting">Habari {{ $customer->name }},</div>
                    <div class="body-text">
                        {{ $bodyContent }}
                    </div>
                    <div class="cta-container">
                        <a href="{{ $url }}" class="btn">Take Survey / Toa Maoni</a>
                    </div>
                    <div class="body-text" style="font-size: 14px; border-top: 1px solid #eee; padding-top: 20px;">
                        Your feedback goes directly to our management team and helps us provide you with the best possible service.
                    </div>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    @php
                        $companyName = \App\Models\SystemSetting::get('company_name', 'TruMark Co. LTD');
                        $companyAddress = \App\Models\SystemSetting::get('company_address', 'Ubungo EACLC & Kimara Stopover, Dar es Salaam, Tanzania');
                    @endphp
                    <div class="social-links">
                        <strong>{{ $companyName }}</strong>
                    </div>
                    <div class="company-info">
                        &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.<br>
                        {{ $companyAddress }}<br>
                        <small>You are receiving this email because you recently interacted with {{ $companyName }}.</small>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
