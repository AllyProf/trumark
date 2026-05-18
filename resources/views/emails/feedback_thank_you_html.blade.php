<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Your Feedback</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8f9fa; padding-bottom: 40px; padding-top: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #4a4a4a; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 1px solid #f1f1f1; }
        .logo { max-width: 180px; height: auto; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .greeting { font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 20px; }
        .body-text { font-size: 16px; margin-bottom: 25px; color: #5a5a5a; }
        .thank-you-box { background-color: #fcf8e3; border: 1px solid #fbeed5; border-radius: 4px; padding: 15px; margin-bottom: 25px; text-align: center; }
        .rating-stars { color: #d4af37; font-size: 20px; margin-bottom: 5px; }
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
                    @if(file_exists(public_path('assets/images/trumark-logo.jpeg')))
                        <img src="{{ $message->embed(public_path('assets/images/trumark-logo.jpeg')) }}" alt="TruMark Logo" class="logo">
                    @else
                        <h2 style="color: #940000; margin: 0; font-weight: 900; letter-spacing: 3px;">TRUMARK</h2>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="content">
                    <div class="greeting">Habari {{ $customer->name }},</div>
                    
                    <div class="body-text">
                        Asante sana kwa kuchukua muda wako kutujazia maoni yako ya huduma. Maoni yako yamefika kwa usimamizi wetu na yatatusaidia kuboresha huduma zetu zaidi ili kukupatia huduma bora kabisa wakati wote!
                    </div>

                    <div class="body-text">
                        Thank you very much for taking your time to provide us with your feedback. Your opinion has reached our management and will guide us in improving our operations to always serve you best!
                    </div>

                    <div class="thank-you-box">
                        <div class="rating-stars">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $feedback->rating)
                                    ★
                                @else
                                    ☆
                                @endif
                            @endfor
                        </div>
                        <div style="font-weight: bold; color: #c09853;">
                            Rating: {{ $feedback->rating }} / 5 Stars
                        </div>
                        @if($feedback->comment)
                            <div style="font-style: italic; margin-top: 10px; color: #5a5a5a; font-size: 14px;">
                                "{{ $feedback->comment }}"
                            </div>
                        @endif
                    </div>

                    <div class="body-text" style="font-size: 14px; border-top: 1px solid #eee; padding-top: 20px; margin-bottom: 0;">
                        Tunaamini katika ushirikiano wetu na tunakuthamini sana kama mteja wetu mkuu. Karibu tena TRUMARK!<br><br>
                        We value your partnership and are truly grateful to have you as our esteemed customer. Welcome back to TRUMARK!
                    </div>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <div class="social-links">
                        <strong>TruMark Co. LTD</strong>
                    </div>
                    <div class="company-info">
                        &copy; {{ date('Y') }} TruMark Co. LTD. All rights reserved.<br>
                        Plot No. 123, Arusha, Tanzania<br>
                        <small>Ulipokea barua pepe hii kwa sababu umewahi kufanya kazi au kuwasiliana na TruMark Co. LTD.</small>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
