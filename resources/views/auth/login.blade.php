<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('vali/css/main.css') }}">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Login - TRUMARK CRM</title>
    <style>
        body, .login-content .logo h1, .login-box .login-head {
            font-family: 'Century Gothic', 'Segoe UI', sans-serif !important;
        }
        /* Full-page background image on html & body */
        html, body {
            background-image: url("{{ asset('assets/images/login_back.jpg') }}") !important;
            background-size: cover !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            background-attachment: fixed !important;
            background-color: #333 !important;
        }
        /* Make ALL framework sections transparent so image shows through */
        .material-half-bg,
        .material-half-bg .cover,
        .login-content {
            background: transparent !important;
        }
        .login-content .logo h1 {
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 5px;
            color: #fff !important;
            text-shadow: 0 2px 8px rgba(0,0,0,0.6);
        }
        .btn-primary {
            background-color: #940000 !important;
            border-color: #940000 !important;
        }
        .login-box .login-head {
            color: #940000 !important;
            margin-bottom: 30px;
        }
        .login-box {
            min-height: 440px !important;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            background: #fff !important;
        }
        /* Footer brand - now merged into floating button */
        .footer-brand { display: none; }

        /* Floating Support Button */
        .floating-support {
            position: fixed;
            bottom: 28px;
            right: 24px;
            display: flex;
            align-items: center;
            gap: 0;
            z-index: 9999;
        }
        .floating-support .support-label {
            background: rgba(255,255,255,0.92);
            color: #222;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 8px 14px;
            border-radius: 20px 0 0 20px;
            border: 1px solid rgba(0,0,0,0.12);
            border-right: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            cursor: default;
            white-space: nowrap;
        }
        .floating-support .support-btn {
            background: #940000;
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(148,0,0,0.4);
            text-decoration: none;
            transition: background 0.2s, transform 0.2s;
            border: 2px solid #fff;
        }
        .floating-support .support-btn:hover {
            background: #b00000;
            transform: scale(1.08);
        }

        /* Password toggle */
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .toggle-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
            padding: 0;
            font-size: 15px;
            line-height: 1;
        }
        .password-wrapper .toggle-eye:hover { color: #940000; }
        .password-wrapper .form-control { padding-right: 36px; }

        /* Spinner inside button */
        .btn .fa-spinner { display: none; }
        .btn.loading .fa-sign-in,
        .btn.loading .btn-icon-default { display: none; }
        .btn.loading .fa-spinner { display: inline-block; }
        .btn.loading { opacity: 0.8; pointer-events: none; }
    </style>
  </head>
  <body>
    <section class="material-half-bg">
      <div class="cover"></div>
    </section>
    <section class="login-content">
      <div class="logo">
        <h1>TRUMARK</h1>
      </div>
      <div class="login-box">
        <form class="login-form" action="{{ route('login') }}" method="POST">
            @csrf
          <h3 class="login-head"><i class="fa fa-lg fa-fw fa-user"></i>SIGN IN</h3>
          <div class="form-group">
            <label class="control-label">EMAIL ADDRESS</label>
            <input class="form-control" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <small class="text-danger mt-1 d-block">{{ $message }}</small>
            @enderror
          </div>
          <div class="form-group">
            <label class="control-label">PASSWORD</label>
            <div class="password-wrapper">
              <input class="form-control" id="password-input" type="password" name="password" placeholder="Password" required>
              <button type="button" class="toggle-eye" id="toggle-password" title="Show/Hide Password">
                <i class="fa fa-eye" id="eye-icon"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <div class="utility">
              <div class="animated-checkbox">
                <label>
                  <input type="checkbox" name="remember"><span class="label-text">Stay Signed In</span>
                </label>
              </div>
              <p class="semibold-text mb-2"><a href="#" data-toggle="flip">Forgot Password?</a></p>
            </div>
          </div>
          <div class="form-group btn-container">
            <button id="login-btn" class="btn btn-primary btn-block" type="submit">
              <i class="fa fa-sign-in fa-lg fa-fw"></i>
              <i class="fa fa-spinner fa-spin fa-lg fa-fw"></i>
              SIGN IN
            </button>
          </div>
        </form>
        <form class="forget-form" action="#">
          <h3 class="login-head"><i class="fa fa-lg fa-fw fa-lock"></i>Forgot Password ?</h3>
          <div class="form-group">
            <p class="text-muted text-center">Please contact your System Administrator to reset your password credentials.</p>
          </div>
          <div class="form-group btn-container">
            <button class="btn btn-primary btn-block" type="button" data-toggle="flip"><i class="fa fa-arrow-left fa-lg fa-fw"></i>BACK TO LOGIN</button>
          </div>
        </form>
      </div>
    </section>
    
    <!-- Floating EMCA Support Button -->
    <div class="floating-support">
        <span class="support-label">POWERED BY &nbsp;<strong>EMCA TECHNOLOGIES LTD</strong></span>
        <a href="https://www.emca.tech/contact" target="_blank" class="support-btn" title="Contact EMCA Technologies">
            <i class="fa fa-external-link"></i>
        </a>
    </div>


    <!-- Essential javascripts for application to work-->
    <script src="{{ asset('vali/js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vali/js/popper.min.js') }}"></script>
    <script src="{{ asset('vali/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vali/js/main.js') }}"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="{{ asset('vali/js/plugins/pace.min.js') }}"></script>
    <script type="text/javascript">
      // Login Page Flipbox Control
      $('.login-content [data-toggle="flip"]').click(function() {
        $('.login-box').toggleClass('flipped');
        return false;
      });

      // Password Show/Hide Toggle
      $('#toggle-password').on('click', function() {
        var input = $('#password-input');
        var icon  = $('#eye-icon');
        if (input.attr('type') === 'password') {
          input.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          input.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });

      // Loading spinner on Sign In submit
      $('.login-form').on('submit', function() {
        var btn = $('#login-btn');
        btn.addClass('loading');
        // Fallback: restore after 8s in case of network error
        setTimeout(function() { btn.removeClass('loading'); }, 8000);
      });
    </script>
  </body>
</html>
