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
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `user_login_logs` MODIFY COLUMN `login_at` DATETIME NULL DEFAULT NULL");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `user_login_logs` MODIFY COLUMN `logout_at` DATETIME NULL DEFAULT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_login_logs', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `user_login_logs` MODIFY COLUMN `login_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `user_login_logs` MODIFY COLUMN `logout_at` TIMESTAMP NULL DEFAULT NULL");
        });
    }
};
