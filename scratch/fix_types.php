<?php

use App\Models\Customer;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$namesToFix = [
    'MARANGU HILLS',
    'MICHAEL A.MCHOME',
    'MWL ANNA SCHOOL',
    'NEBRIX',
    'OPTIMAL',
    'SALUMU PHILEMON'
];

foreach ($namesToFix as $name) {
    $customers = Customer::where('name', 'LIKE', '%' . $name . '%')->get();
    foreach ($customers as $customer) {
        $oldType = $customer->type;
        $customer->type = 'School';
        $customer->save();
        echo "Updated '{$customer->name}': Changed Type from '{$oldType}' to 'School'.\n";
    }
}
