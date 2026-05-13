<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Mail\SurveyInvitation;
use App\Models\Customer;

try {
    // Find a sample customer to use for the test
    $customer = Customer::whereNotNull('email')->first();
    
    if (!$customer) {
        // Create a dummy customer if none exists with email
        $customer = new Customer();
        $customer->name = 'Ally Test';
        $customer->email = 'allyprof7@gmail.com';
        $customer->survey_uuid = (string) \Illuminate\Support\Str::uuid();
    }

    Mail::to('allyprof7@gmail.com')->send(new SurveyInvitation($customer));
    echo "SUCCESS: Professional HTML test email sent to allyprof7@gmail.com";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
