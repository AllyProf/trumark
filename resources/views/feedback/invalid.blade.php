<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Not Found - TRUMARK</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f5f5f5;
            color: #333;
            padding: 20px;
        }
        .box {
            background: #fff;
            border-top: 4px solid #940000;
            border-radius: 8px;
            max-width: 480px;
            width: 100%;
            padding: 32px 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
        }
        h1 { color: #940000; font-size: 22px; margin: 0 0 12px; }
        p { margin: 0 0 10px; line-height: 1.5; color: #555; }
        .code { font-size: 12px; color: #888; word-break: break-all; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Survey link not found</h1>
        <p>This feedback link is invalid, expired, or was created on a different system database.</p>
        <p>Please contact TRUMARK or ask your sales officer to send a new survey invitation.</p>
        @if(!empty($uuid))
            <p class="code">Ref: {{ $uuid }}</p>
        @endif
    </div>
</body>
</html>
