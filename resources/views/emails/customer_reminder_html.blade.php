<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reminder - TruMark Co. LTD</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f7fa; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #940000; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px; }
        .content { padding: 40px; }
        .content p { margin-bottom: 20px; font-size: 16px; color: #555; }
        .footer { background-color: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
        .logo { max-width: 150px; margin-bottom: 10px; }
        .highlight { color: #940000; font-weight: bold; }
        .card { background: #fdfdfd; border: 1px solid #eee; padding: 20px; border-radius: 6px; border-left: 4px solid #940000; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TRUMARK CO. LTD</h1>
        </div>
        <div class="content">
            <p>Dear <span class="highlight">{{ $customer->name }}</span>,</p>
            
            <p>We hope this email finds you well.</p>

            <div class="card">
                {{ $reminderMessage }}
            </div>

            <p>If you have any questions or require further assistance, please do not hesitate to contact our customer support team or your dedicated sales officer.</p>

            <p>Thank you for choosing <strong>TruMark Co. LTD</strong>. We value your business and look forward to continuing our partnership.</p>
            
            <p>Best Regards,<br><strong>The TruMark Team</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} TruMark Co. LTD. All rights reserved.<br>
            Professional Equipment & Quality Services
        </div>
    </div>
</body>
</html>
