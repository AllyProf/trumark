<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - TRUMARK</title>
    <!-- Main CSS (Vali Admin) -->
    <link rel="stylesheet" type="text/css" href="{{ asset('vali/css/main.css') }}">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            background: url("{{ asset('assets/images/feedback (2).jpg') }}") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Century Gothic', 'Segoe UI', sans-serif;
            padding: 20px;
        }
        .tile {
            border-top: 4px solid #28a745;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 0.98);
            width: 100%;
            max-width: 500px;
            border-radius: 8px;
            text-align: center;
            padding: 50px !important;
        }
        .brand-logo {
            font-weight: 900;
            color: #940000;
            font-size: 24px;
            letter-spacing: 3px;
            text-align: center;
            margin-bottom: 20px;
            display: block;
        }
        .footer-text {
            color: #ffffff !important;
            font-size: 12px;
            margin-top: 25px;
            text-align: center;
            font-weight: 600;
            background: rgba(148, 0, 0, 0.85);
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(148, 0, 0, 0.25);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 d-flex flex-column align-items-center justify-content-center">
                <div class="tile">
                    <span class="brand-logo">TRUMARK</span>
                    <div class="mb-4">
                        <i class="fa fa-check-circle fa-5x text-success"></i>
                    </div>
                    <h2 class="text-success mb-3">THANK YOU!</h2>
                    <p class="lead text-muted">We have received your feedback. Thank you for helping us improve our services.</p>
                    <hr>
                    <p class="text-muted small">You can now close this window.</p>
                    
                    <div class="mt-4">
                        <strong style="color: #940000; letter-spacing: 2px;">TRUMARK CRM</strong>
                    </div>
                </div>
                
                <div class="footer-text">
                    &copy; {{ date('Y') }} TRUMARK Performance Engine. Powered by EMCA Technologies.
                </div>
            </div>
        </div>
    </div>

    <!-- Essential javascripts for application to work-->
    <script src="{{ asset('vali/js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vali/js/bootstrap.min.js') }}"></script>
</body>
</html>
