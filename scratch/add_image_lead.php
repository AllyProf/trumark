<?php

use App\Models\Customer;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customer = Customer::create([
    'sales_officer_id' => 1, // Ally Ally
    'branch_id'        => 3, // Kimara Stopover
    'type'             => 'School',
    'name'             => 'Lutheran Primary School',
    'contact_person'   => 'Lucas Petro Tago',
    'position'         => 'Academic Master',
    'phone'            => 'No Phone Provided', // Extracted 'Primary 73School' as a placeholder error in sheet
    'email'            => 'tiagoluca2018@gmail.com',
    'region'           => 'Ruvuma',
    'district'         => 'Mbinga',
    'source'           => 'Conference',
    'status'           => 'New Customer',
    'school_level'     => 'Secondary',
    'buying_stage'     => 'Inquiry',
]);

if ($customer) {
    \App\Services\KpiService::recordActivity('REG_NEW_POTENTIAL', 1, $customer->id);
    \App\Services\KpiService::recordActivity('NEW_ORG_LEAD', 1, $customer->id);
    echo "Successfully ADDED lead: Lutheran Primary School (ID: {$customer->id})\n";
}
