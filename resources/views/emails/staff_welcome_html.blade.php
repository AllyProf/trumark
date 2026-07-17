<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to TruMark</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8f9fa; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #4a4a4a; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 1px solid #f1f1f1; }
        .logo { max-width: 180px; height: auto; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .greeting { font-size: 22px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .sub-greeting { font-size: 18px; color: #5a5a5a; margin-bottom: 25px; }
        .body-text { font-size: 16px; margin-bottom: 25px; color: #5a5a5a; }
        .cred-box { background-color: #f9f9f9; border: 1px dashed #ddd; padding: 25px; border-radius: 5px; margin: 25px 0; }
        .cred-item { margin-bottom: 10px; font-size: 16px; }
        .cred-label { font-weight: bold; color: #940000; width: 90px; display: inline-block; }
        .cta-container { text-align: center; margin: 35px 0; }
        .btn { background-color: #940000; color: #ffffff !important; padding: 15px 35px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block; }
        .footer-text { font-size: 16px; margin-top: 30px; color: #2c3e50; font-weight: bold; }
        .footer { background-color: #f1f1f1; padding: 30px; text-align: center; font-size: 13px; color: #888888; }
        @media screen and (max-width: 600px) {
            .main { width: 95% !important; }
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
                    <div class="greeting">Welcome to TruMark, {{ $user->name }}</div>
                    <div class="sub-greeting">Your staff account has been successfully created.</div>
                    
                    <div class="body-text">
                        You can now access your dashboard using the details below:
                    </div>
                    
                    <div class="cred-box">
                        <div class="cred-item"><span class="cred-label">Branch :</span> {{ $user->branch ? $user->branch->name : 'Global' }}</div>
                        <div class="cred-item"><span class="cred-label">Role :</span> {{ ucwords(str_replace('_', ' ', $user->role)) }}</div>
                        <div class="cred-item"><span class="cred-label">Username :</span> {{ $user->email }}</div>
                        <div class="cred-item"><span class="cred-label">Password :</span> {{ $plainPassword }}</div>
                    </div>
                    
                    <div class="cta-container">
                        <a href="{{ url('/') }}" class="btn">Access Dashboard</a>
                    </div>
                    
                    <div class="body-text">
                        Please log in and change your password after your first access for security purposes.
                    </div>
                    
                    <div class="body-text" style="font-style: italic;">
                        If you need help, contact your Manager.
                    </div>
                    
                    <div class="footer-text">
                        - TruMark Team
                    </div>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    @php
                        $companyName = \App\Models\SystemSetting::get('company_name', 'TruMark Co. LTD');
                        $companyAddress = \App\Models\SystemSetting::get('company_address', 'Ubungo EACLC & Kimara Stopover, Dar es Salaam, Tanzania');
                    @endphp
                    <strong>{{ $companyName }}</strong><br>
                    {{ $companyAddress }}<br>
                    &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
