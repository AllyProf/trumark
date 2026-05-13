<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'ally@gmail.com')->first();
if ($user) {
    $user->update(['password' => Hash::make('password')]);
    echo "Ally password updated successfully.\n";
} else {
    echo "Ally not found.\n";
}
