<?php

use App\Models\Customer;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customer = Customer::where('name', 'LIKE', '%DAUDI MADETE%')->first();

if ($customer) {
    $customer->update([
        'email' => 'tiagoluca2018@gmail.com'
    ]);
    echo "Successfully UPDATED email for: DAUDI MADETE (ID: {$customer->id})\n";
} else {
    echo "Lead DAUDI MADETE not found.\n";
}
