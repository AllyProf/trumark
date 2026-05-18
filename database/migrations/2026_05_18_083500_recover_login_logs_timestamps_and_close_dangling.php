<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure table columns are DATETIME and don't have MySQL auto-update trigger
        try {
            DB::statement("ALTER TABLE `user_login_logs` MODIFY COLUMN `login_at` DATETIME NULL DEFAULT NULL");
            DB::statement("ALTER TABLE `user_login_logs` MODIFY COLUMN `logout_at` DATETIME NULL DEFAULT NULL");
        } catch (\Exception $e) {
            // Ignore if columns are already updated
        }

        // 2. Restore login_at to created_at and clean up data
        DB::table('user_login_logs')->chunkById(100, function ($logs) {
            foreach ($logs as $log) {
                $created = Carbon::parse($log->created_at);
                $loginAt = $log->created_at;
                $logoutAt = $log->logout_at;
                $duration = $log->duration_minutes;

                // 3. Auto-close dangling active sessions older than 24 hours
                if (!$logoutAt && $created->lt(now()->subHours(24))) {
                    // Close the session automatically after 8 hours (480 mins)
                    $logoutAt = $created->copy()->addHours(8);
                    $duration = 480;
                } elseif ($logoutAt) {
                    // Recalculate accurate duration using the true login time (created_at)
                    $parsedLogout = Carbon::parse($logoutAt);
                    $duration = $created->diffInMinutes($parsedLogout);
                    if ($duration < 0) {
                        $duration = 0;
                    }
                }

                DB::table('user_login_logs')
                    ->where('id', $log->id)
                    ->update([
                        'login_at' => $loginAt,
                        'logout_at' => $logoutAt,
                        'duration_minutes' => $duration
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data recovery down operations are not necessary
    }
};
