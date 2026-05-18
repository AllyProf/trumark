<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_login_logs', function (Blueprint $table) {
            $table->string('isp')->nullable()->after('location');
        });

        // Data migration: Backfill ISP network data for all existing records
        try {
            \Illuminate\Support\Facades\DB::table('user_login_logs')->chunkById(100, function ($logs) {
                foreach ($logs as $log) {
                    $ip = $log->ip_address;
                    $isp = 'Local Network';

                    if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
                        try {
                            $response = \Illuminate\Support\Facades\Http::timeout(2)->get("http://ip-api.com/json/{$ip}");
                            if ($response->successful()) {
                                $data = $response->json();
                                if (isset($data['status']) && $data['status'] === 'success') {
                                    $isp = $data['isp'] ?? 'Unknown';
                                } else {
                                    $isp = 'Unknown';
                                }
                            }
                        } catch (\Exception $e) {
                            $isp = 'Unknown';
                        }
                    }

                    \Illuminate\Support\Facades\DB::table('user_login_logs')
                        ->where('id', $log->id)
                        ->update(['isp' => $isp]);
                }
            });
        } catch (\Exception $e) {
            // Silently allow schema update if data backfill fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_login_logs', function (Blueprint $table) {
            $table->dropColumn('isp');
        });
    }
};
