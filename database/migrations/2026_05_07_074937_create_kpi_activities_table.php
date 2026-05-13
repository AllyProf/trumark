<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. KPI Activities Ledger
        Schema::create('kpi_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('activity_type'); // e.g., 'registration', 'sale', 'followup', 'visit', 'discipline'
            $table->string('activity_code'); // e.g., 'REG_NEW_POTENTIAL', 'SALE_CLOSED_NEW'
            $table->integer('points');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Login & Usage Tracking
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_activities');
        Schema::dropIfExists('user_login_logs');
    }
};
