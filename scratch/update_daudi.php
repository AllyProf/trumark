<?php

use App\Models\Customer;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customer = Customer::where('name', 'LIKE', '%DAUDI MADETE%')->first();

if ($customer) {
    $customer->update([
        'school_level' => 'Primary',
        'position'     => 'Academic Master'
    ]);
    echo "Successfully UPDATED lead: DAUDI MADETE (ID: {$customer->id})\n";
} else {
    echo "Lead DAUDI MADETE not found.\n";
}
