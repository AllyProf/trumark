<?php

use App\Models\User;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::find(1);
if ($user) {
    $user->branch_id = 3;
    $user->save();
    echo "User Ally Ally (ID 1) branch updated to 3 (Kimara Stopover).\n";
} else {
    echo "User ID 1 not found.\n";
}
