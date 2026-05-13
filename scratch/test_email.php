<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

try {
    Mail::raw('Hello! This is a final test email from your new TruMark CRM configuration. If you see this, your email system is 100% ready!', function ($message) {
        $message->to('allyprof7@gmail.com')->subject('TruMark CRM: Email Connection Test');
    });
    echo "SUCCESS: Test email sent to allyprof7@gmail.com";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
