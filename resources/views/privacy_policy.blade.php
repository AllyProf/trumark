<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — TruMark Co. LTD</title>
    <meta name="description" content="Privacy Policy for TruMark Co. LTD — Learn how we collect, use, and protect your personal data.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.8;
        }

        /* NAV */
        nav {
            background: #940000;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }
        nav .brand {
            color: white;
            font-size: 22px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.5px;
        }
        nav .brand span { color: #ffcccb; }
        nav a.back-btn {
            color: white;
            text-decoration: none;
            font-size: 14px;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 6px 16px;
            border-radius: 20px;
            transition: background 0.2s;
        }
        nav a.back-btn:hover { background: rgba(255,255,255,0.15); }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, #940000 0%, #5a0000 100%);
            color: white;
            text-align: center;
            padding: 60px 20px 70px;
        }
        .hero h1 {
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .hero p {
            font-size: 16px;
            opacity: 0.85;
            max-width: 600px;
            margin: 0 auto;
        }
        .hero .badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 5px 16px;
            border-radius: 30px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        /* CONTAINER */
        .container {
            max-width: 860px;
            margin: -30px auto 60px;
            padding: 0 20px;
        }

        /* CARD */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            padding: 50px 60px;
        }

        /* TABLE OF CONTENTS */
        .toc {
            background: #fff5f5;
            border-left: 4px solid #940000;
            border-radius: 0 8px 8px 0;
            padding: 20px 24px;
            margin-bottom: 40px;
        }
        .toc h3 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #940000;
            margin-bottom: 12px;
        }
        .toc ol {
            padding-left: 18px;
        }
        .toc ol li {
            margin-bottom: 4px;
        }
        .toc ol li a {
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        .toc ol li a:hover { color: #940000; }

        /* SECTION */
        .section {
            margin-bottom: 40px;
            scroll-margin-top: 80px;
        }
        .section h2 {
            font-size: 20px;
            font-weight: 700;
            color: #940000;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section h2 .num {
            background: #940000;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .section p {
            font-size: 15px;
            color: #444;
            margin-bottom: 12px;
        }
        .section ul {
            padding-left: 20px;
            margin-bottom: 12px;
        }
        .section ul li {
            font-size: 15px;
            color: #444;
            margin-bottom: 6px;
        }

        /* HIGHLIGHT BOX */
        .highlight-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 16px 0;
            font-size: 14px;
            color: #555;
            border: 1px solid #eee;
        }

        /* CONTACT */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .contact-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            border: 1px solid #eee;
        }
        .contact-item .icon { font-size: 22px; margin-bottom: 6px; }
        .contact-item .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #999; }
        .contact-item .value { font-size: 14px; font-weight: 600; color: #333; margin-top: 4px; }

        /* FOOTER */
        footer {
            text-align: center;
            padding: 30px;
            font-size: 13px;
            color: #999;
            border-top: 1px solid #eee;
        }
        footer strong { color: #940000; }

        /* DATE */
        .last-updated {
            text-align: right;
            font-size: 12px;
            color: #aaa;
            margin-bottom: 30px;
        }

        @media (max-width: 640px) {
            .card { padding: 28px 20px; }
            .hero h1 { font-size: 26px; }
            nav { padding: 14px 20px; }
        }
    </style>
</head>
<body>

<nav>
    <a class="brand" href="/">Tru<span>Mark</span></a>
    <a href="/" class="back-btn">← Home</a>
</nav>

<div class="hero">
    <div class="badge">Legal Document</div>
    <h1>🔒 Privacy Policy</h1>
    <p>We are committed to protecting your personal data. This policy explains clearly how we collect, use, and safeguard your information.</p>
</div>

<div class="container">
    <div class="card">

        <div class="last-updated">Last Updated: <strong>May 23, 2025</strong> &nbsp;|&nbsp; Effective Date: <strong>May 23, 2025</strong></div>

        <div class="toc">
            <h3>📋 Table of Contents</h3>
            <ol>
                <li><a href="#about">About TruMark</a></li>
                <li><a href="#information">Information We Collect</a></li>
                <li><a href="#whatsapp">WhatsApp Data & Messaging</a></li>
                <li><a href="#usage">How We Use Your Information</a></li>
                <li><a href="#sharing">How We Share Your Information</a></li>
                <li><a href="#retention">Data Retention</a></li>
                <li><a href="#rights">Your Rights</a></li>
                <li><a href="#security">Data Security</a></li>
                <li><a href="#children">Children's Privacy</a></li>
                <li><a href="#changes">Changes to This Policy</a></li>
                <li><a href="#contact">Contact Us</a></li>
            </ol>
        </div>

        <!-- 1 -->
        <div class="section" id="about">
            <h2><span class="num">1</span> About TruMark</h2>
            <p><strong>TruMark Co. LTD</strong> ("TruMark," "we," "us," or "our") is a stationery and educational materials company based in <strong>Tanzania</strong>. We operate physical branches and a WhatsApp-based communication service to assist customers with school books, office supplies, printing, delivery, and related services.</p>
            <p>This Privacy Policy applies to all individuals who interact with TruMark through any channel, including our website, WhatsApp Business messaging, and in-store services.</p>
        </div>

        <!-- 2 -->
        <div class="section" id="information">
            <h2><span class="num">2</span> Information We Collect</h2>
            <p>We collect information in the following ways:</p>
            <ul>
                <li><strong>Contact Details:</strong> Name, phone number, email address, and location when you register as a customer or contact us.</li>
                <li><strong>Business Information:</strong> School or organization name, buying type, and service preferences.</li>
                <li><strong>Transaction Data:</strong> Order history, quotation requests, and payment references.</li>
                <li><strong>Communication Data:</strong> Messages and conversations you send us via WhatsApp or other channels.</li>
                <li><strong>Device & Technical Data:</strong> IP address, browser type, and usage data when visiting our web services.</li>
            </ul>
        </div>

        <!-- 3 -->
        <div class="section" id="whatsapp">
            <h2><span class="num">3</span> WhatsApp Data & Messaging</h2>
            <p>TruMark uses the <strong>WhatsApp Business Platform (Meta)</strong> to communicate with customers. When you send a message to our WhatsApp Business number, the following applies:</p>
            <div class="highlight-box">
                📱 <strong>Messages are processed on our secure servers</strong> in order to provide automated AI-powered responses, route your inquiry to the correct department, or escalate to a human support agent where necessary.
            </div>
            <ul>
                <li>Your phone number and message content are stored in our secure CRM system to maintain conversation history and provide better support.</li>
                <li>Automated responses may be generated by an AI engine (powered by Google Gemini) trained to represent TruMark services.</li>
                <li>If you request human support, your phone number may be shared with a TruMark staff member to enable a direct conversation.</li>
                <li>We do <strong>not</strong> sell your WhatsApp conversations or phone number to any third party for advertising purposes.</li>
                <li>Message delivery and read receipts are managed by Meta (WhatsApp) under their own Privacy Policy: <a href="https://www.whatsapp.com/legal/privacy-policy" target="_blank">whatsapp.com/legal/privacy-policy</a></li>
            </ul>
        </div>

        <!-- 4 -->
        <div class="section" id="usage">
            <h2><span class="num">4</span> How We Use Your Information</h2>
            <p>We use your personal information for the following purposes:</p>
            <ul>
                <li>To respond to your inquiries, orders, and quotation requests.</li>
                <li>To send follow-up reminders about products, services, or pending orders (with your consent).</li>
                <li>To improve our customer service and product offering.</li>
                <li>To process and deliver orders.</li>
                <li>To comply with legal obligations under Tanzanian law.</li>
                <li>To send service-related communications (not promotional, unless consented).</li>
            </ul>
        </div>

        <!-- 5 -->
        <div class="section" id="sharing">
            <h2><span class="num">5</span> How We Share Your Information</h2>
            <p>TruMark does <strong>not sell</strong> your personal data. We may share your information only in these limited circumstances:</p>
            <ul>
                <li><strong>Service Providers:</strong> Third-party platforms like Google (Gemini AI), Meta (WhatsApp API), and email providers that help us operate our services. These providers are bound by their own privacy policies.</li>
                <li><strong>Internal Staff:</strong> Authorized TruMark sales officers and support agents who need access to serve you.</li>
                <li><strong>Legal Requirements:</strong> If required by law, court order, or government authority in Tanzania.</li>
                <li><strong>Business Transfer:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred with appropriate notice.</li>
            </ul>
        </div>

        <!-- 6 -->
        <div class="section" id="retention">
            <h2><span class="num">6</span> Data Retention</h2>
            <p>We retain your personal data for as long as necessary to fulfill the purposes described in this policy, or as required by Tanzanian law.</p>
            <ul>
                <li>Customer records are kept for a minimum of <strong>3 years</strong> for business and accounting purposes.</li>
                <li>WhatsApp communication logs are retained for <strong>12 months</strong> and then permanently deleted.</li>
                <li>You may request deletion of your data at any time by contacting us (see Section 11).</li>
            </ul>
        </div>

        <!-- 7 -->
        <div class="section" id="rights">
            <h2><span class="num">7</span> Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li><strong>Access</strong> the personal data we hold about you.</li>
                <li><strong>Correct</strong> any inaccurate or incomplete data.</li>
                <li><strong>Delete</strong> your personal data (subject to legal requirements).</li>
                <li><strong>Opt-out</strong> of marketing or follow-up communications at any time.</li>
                <li><strong>Object</strong> to any processing of your data that you believe is unlawful.</li>
            </ul>
            <p>To exercise any of these rights, please contact us using the details in Section 11.</p>
        </div>

        <!-- 8 -->
        <div class="section" id="security">
            <h2><span class="num">8</span> Data Security</h2>
            <p>We take reasonable technical and organizational measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction. These measures include:</p>
            <ul>
                <li>HTTPS encryption for all web traffic.</li>
                <li>Password-protected, access-controlled CRM systems.</li>
                <li>Role-based access control — only authorized staff can view customer data.</li>
                <li>Regular security reviews and audit logging of all system activity.</li>
            </ul>
            <p>However, no system is 100% secure. If you believe your data has been compromised, please contact us immediately.</p>
        </div>

        <!-- 9 -->
        <div class="section" id="children">
            <h2><span class="num">9</span> Children's Privacy</h2>
            <p>Our services are intended for adults and businesses. We do not knowingly collect personal information directly from children under the age of 13. Parents and guardians making purchases on behalf of children are responsible for providing their own contact information.</p>
        </div>

        <!-- 10 -->
        <div class="section" id="changes">
            <h2><span class="num">10</span> Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time to reflect changes in our practices, technology, or legal requirements. When we do, we will update the "Last Updated" date at the top of this page.</p>
            <p>Continued use of our services after any changes constitutes your acceptance of the updated policy. We encourage you to review this page periodically.</p>
        </div>

        <!-- 11 -->
        <div class="section" id="contact">
            <h2><span class="num">11</span> Contact Us</h2>
            <p>If you have any questions about this Privacy Policy or wish to exercise your data rights, please reach out to us:</p>
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="icon">🏢</div>
                    <div class="label">Company</div>
                    <div class="value">TruMark Co. LTD</div>
                </div>
                <div class="contact-item">
                    <div class="icon">📍</div>
                    <div class="label">Location</div>
                    <div class="value">Ubungo & Kimara, Dar es Salaam, Tanzania</div>
                </div>
                <div class="contact-item">
                    <div class="icon">📞</div>
                    <div class="label">WhatsApp / Phone</div>
                    <div class="value">+255 794 467 694</div>
                </div>
                <div class="contact-item">
                    <div class="icon">✉️</div>
                    <div class="label">Email</div>
                    <div class="value">system@trumark.co.tz</div>
                </div>
            </div>
        </div>

    </div>
</div>

<footer>
    &copy; {{ date('Y') }} <strong>TruMark Co. LTD</strong>. All rights reserved. &nbsp;|&nbsp; Tanzania
</footer>

</body>
</html>
