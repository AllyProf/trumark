<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback - TRUMARK</title>
    <!-- Main CSS (Vali Admin) -->
    <link rel="stylesheet" type="text/css" href="{{ asset('vali/css/main.css') }}">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            background: url("{{ asset('assets/images/feedback (2).jpg') }}") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            font-family: 'Century Gothic', 'Segoe UI', sans-serif;
            margin: 0;
        }
        .tile {
            border-top: 4px solid #940000;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            background: rgba(255, 255, 255, 0.98);
            width: 100%;
            max-width: 500px;
            border-radius: 8px;
            padding: 30px !important;
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
        .submit-btn {
            background: linear-gradient(135deg, #940000 0%, #b30000 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #b30000 0%, #940000 100%);
            box-shadow: 0 5px 15px rgba(148, 0, 0, 0.3);
            transform: translateY(-1px);
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

    <div class="container py-5">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-12 d-flex flex-column align-items-center justify-content-center">
                <div class="tile">
                    <span class="brand-logo">TRUMARK</span>
                    <h3 class="tile-title text-center">Feedback Survey</h3>
                    <div class="tile-body">
                        <p class="text-muted text-center mb-4">Hello <strong>{{ $customer->name }}</strong>, please rate your experience with us.</p>
                        
                        @if($customer->salesOfficer)
                            <div class="alert alert-info py-2 small">
                                <i class="fa fa-info-circle mr-1"></i> Served by: <strong>{{ $customer->salesOfficer->name }}</strong>
                            </div>
                        @endif

                        <form action="{{ route('feedback.store', $customer->survey_uuid) }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <label class="control-label font-weight-bold small">How would you rate our service?</label>
                                <select name="rating" class="form-control" required>
                                    <option value="">-- Select Rating --</option>
                                    <option value="5">⭐⭐⭐⭐⭐ Excellent (5 Stars)</option>
                                    <option value="4">⭐⭐⭐⭐ Very Good (4 Stars)</option>
                                    <option value="3">⭐⭐⭐ Good (3 Stars)</option>
                                    <option value="2">⭐⭐ Fair (2 Stars)</option>
                                    <option value="1">⭐ Poor (1 Star)</option>
                                </select>
                            </div>

                            <div class="form-group mt-3">
                                <label class="control-label font-weight-bold small">Any additional comments?</label>
                                <textarea name="comment" class="form-control" rows="4" placeholder="Enter your comments here..."></textarea>
                            </div>

                            <div class="form-group mt-4">
                                <button class="btn btn-primary btn-block submit-btn" type="submit">
                                    <i class="fa fa-paper-plane mr-2"></i> Submit Feedback
                                </button>
                            </div>
                        </form>
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
    <script src="{{ asset('vali/js/popper.min.js') }}"></script>
    <script src="{{ asset('vali/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vali/js/main.js') }}"></script>
</body>
</html>
