<?php

use App\Models\Customer;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\KpiService;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Load from file
$jsonPath = __DIR__ . '/leads.json';
if (!file_exists($jsonPath)) {
    die("Error: leads.json not found at {$jsonPath}");
}

$jsonData = file_get_contents($jsonPath);
$customers = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("JSON Decode Error: " . json_last_error_msg());
}

// User: Ally Ally (ID 1)
$user = User::find(1); 
$branchId = 3; // Kimara Stopover

$count = 0;
foreach ($customers as $c) {
    $type = trim($c['Type'] ?? '');
    $nameRaw = trim($c['Name'] ?? '');
    $contact = trim($c['Contact Person'] ?? '');
    $sLevel = trim($c['School Level'] ?? '');

    // SMART NAME FALLBACK: If name is empty, use Contact Person. If that's empty too, use a timestamped placeholder.
    $name = !empty($nameRaw) ? $nameRaw : (!empty($contact) ? $contact : 'New Lead (' . date('d-m-Y H:i') . ')');

    // Auto-detect School type
    if (empty($type) && !empty($sLevel)) {
        $type = 'School';
    }
    if (empty($type) && (str_contains(strtolower($name), 'school') || str_contains(strtolower($name), 'primary') || str_contains(strtolower($name), 'secondary'))) {
        $type = 'School';
    }
    if (empty($type)) $type = 'Potential Customer';

    $reqRaw = trim($c['Requirements'] ?? '');
    $requirements = !empty($reqRaw) ? array_map('trim', explode(',', $reqRaw)) : [];

    $customer = Customer::create([
        'sales_officer_id' => $user->id,
        'branch_id'        => $branchId,
        'type'             => $type,
        'name'             => $name,
        'contact_person'   => $contact,
        'position'         => trim($c['Position'] ?? ''),
        'phone'            => trim($c['Phone'] ?? 'No Phone Provided'),
        'email'            => trim($c['Email'] ?? ''),
        'region'           => trim($c['Region'] ?? ''),
        'district'         => trim($c['District'] ?? ''),
        'source'           => trim($c['Source'] ?? 'Bulk Import'),
        'status'           => trim($c['Status'] ?? 'Potential Customer'),
        'requirements'     => $requirements,
        'school_level'     => $sLevel,
        'buying_stage'     => 'Inquiry',
        'survey_uuid'      => (string) \Illuminate\Support\Str::uuid(),
    ]);

    if ($customer) {
        $count++;
        KpiService::recordActivity('REG_NEW_POTENTIAL', $user->id, $customer->id);
        if (strtolower($customer->source) === 'referral') {
            KpiService::recordActivity('REFERRAL_EXISTING', $user->id, $customer->id);
        }
        if (in_array(strtolower($type), ['school', 'company', 'organization'])) {
            KpiService::recordActivity('NEW_ORG_LEAD', $user->id, $customer->id);
        }
    }
}

echo "Successfully imported {$count} customers.";
