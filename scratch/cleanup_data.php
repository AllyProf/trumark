<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = ['customers', 'sms_logs', 'customer_surveys'];

Schema::disableForeignKeyConstraints();

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        DB::table($table)->truncate();
        echo "CLEARED: $table\n";
    } else {
        echo "SKIP: $table (not found)\n";
    }
}

Schema::enableForeignKeyConstraints();
echo "DATABASE CLEANUP COMPLETE.";
