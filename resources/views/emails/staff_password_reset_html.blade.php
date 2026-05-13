<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - TruMark Co. LTD</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8f9fa; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #4a4a4a; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 1px solid #f1f1f1; }
        .logo { max-width: 180px; height: auto; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .greeting { font-size: 22px; font-weight: bold; color: #2c3e50; margin-bottom: 20px; }
        .body-text { font-size: 16px; margin-bottom: 25px; color: #5a5a5a; }
        .cred-box { background-color: #fff3f3; border: 1px solid #ffcdd2; padding: 20px; border-radius: 5px; margin: 25px 0; text-align: center; }
        .new-pass { font-size: 24px; font-weight: bold; color: #940000; letter-spacing: 2px; }
        .cta-container { text-align: center; margin: 35px 0; }
        .btn { background-color: #940000; color: #ffffff !important; padding: 15px 35px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block; }
        .footer { background-color: #f1f1f1; padding: 30px; text-align: center; font-size: 13px; color: #888888; }
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
                    <div class="greeting">Password Reset 🔐</div>
                    <div class="body-text">
                        Habari {{ $user->name }}, we have reset your password for the **TruMark CRM** as requested. 
                    </div>
                    <div class="body-text">
                        Your new temporary password is:
                    </div>
                    <div class="cred-box">
                        <div class="new-pass">{{ $newPassword }}</div>
                    </div>
                    <div class="cta-container">
                        <a href="{{ url('/') }}" class="btn">Login to CRM</a>
                    </div>
                    <div class="body-text" style="font-size: 14px; color: #888;">
                        Please log in and change this password immediately to secure your account.
                    </div>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <strong>TruMark Co. LTD</strong><br>
                    &copy; {{ date('Y') }} TruMark Co. LTD. All rights reserved.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
