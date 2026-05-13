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
        if (!Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            // Check if foreign key exists is harder, so we'll just try to add it
            try {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            } catch (\Exception $e) {
                // Already exists or other error
            }
        });

        if (!Schema::hasColumn('customers', 'branch_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            try {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            } catch (\Exception $e) {
                // Already exists or other error
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
