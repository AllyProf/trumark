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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'tin_no')) {
                $table->string('tin_no')->nullable()->after('position');
            }
            if (!Schema::hasColumn('customers', 'alternative_phone')) {
                $table->string('alternative_phone')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'region')) {
                $table->string('region')->nullable()->after('email');
            }
            if (!Schema::hasColumn('customers', 'district')) {
                $table->string('district')->nullable()->after('region');
            }
            if (!Schema::hasColumn('customers', 'ward')) {
                $table->string('ward')->nullable()->after('district');
            }
            if (!Schema::hasColumn('customers', 'address')) {
                $table->string('address')->nullable()->after('ward');
            }
            if (!Schema::hasColumn('customers', 'landmark')) {
                $table->string('landmark')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'detailed_requirement')) {
                $table->text('detailed_requirement')->nullable()->after('landmark');
            }
            if (!Schema::hasColumn('customers', 'estimated_monthly_value')) {
                $table->decimal('estimated_monthly_value', 15, 2)->default(0)->after('detailed_requirement');
            }
            if (!Schema::hasColumn('customers', 'urgency')) {
                $table->string('urgency')->default('Medium')->after('estimated_monthly_value');
            }
            if (!Schema::hasColumn('customers', 'expected_purchase_date')) {
                $table->date('expected_purchase_date')->nullable()->after('urgency');
            }
            if (!Schema::hasColumn('customers', 'officer_name')) {
                $table->string('officer_name')->nullable()->after('expected_purchase_date');
            }
            if (!Schema::hasColumn('customers', 'date_contacted')) {
                $table->date('date_contacted')->nullable()->after('officer_name');
            }
            if (!Schema::hasColumn('customers', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('buying_stage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'tin_no', 'alternative_phone', 'region', 'district', 'ward', 'address', 'landmark', 
                'detailed_requirement', 'estimated_monthly_value', 'urgency', 'expected_purchase_date', 
                'officer_name', 'date_contacted', 'payment_terms'
            ]);
        });
    }
};
