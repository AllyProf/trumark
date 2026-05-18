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
        try {
            $loginLogs = \Illuminate\Support\Facades\DB::table('user_login_logs')->get();

            foreach ($loginLogs as $log) {
                \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
                    'user_id'    => $log->user_id,
                    'action'     => 'User logged in',
                    'category'   => 'Authentication',
                    'ip_address' => $log->ip_address,
                    'location'   => $log->location,
                    'isp'        => $log->isp,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->login_at,
                    'updated_at' => $log->login_at
                ]);
            }
        } catch (\Exception $e) {
            // Silently complete if table mapping fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('audit_logs')->where('action', 'User logged in')->delete();
    }
};
