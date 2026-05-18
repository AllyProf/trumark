<?php

use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\KpiActivity;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$targets = [
    ['name' => 'BENJAA', 'phone' => '+255749719998'],
    ['name' => 'OFENI', 'phone' => '+255744341239'],
    ['name' => 'OFEN', 'phone' => '+255744341239']
];

foreach ($targets as $target) {
    $customers = Customer::where('name', 'LIKE', '%' . $target['name'] . '%')
        ->orWhere('phone', 'LIKE', '%' . $target['phone'] . '%')
        ->get();

    foreach ($customers as $customer) {
        $name = $customer->name;
        $id = $customer->id;
        
        // Clean up associated records first
        SmsLog::where('customer_id', $id)->delete();
        KpiActivity::where('customer_id', $id)->delete();
        
        // Delete the customer
        $customer->delete();
        echo "Successfully DELETED lead: {$name} (ID: {$id})\n";
    }
}
