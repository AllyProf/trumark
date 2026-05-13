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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('Potential Customer'); // New, Potential, Existing, Inactive, VIP
            $table->string('type')->nullable(); // Company, School, Parent, Walk in
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('position')->nullable();
            $table->string('phone');
            $table->string('alternative_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tin')->nullable();
            
            // Location
            $table->string('region')->nullable();
            $table->string('district')->nullable();
            $table->string('ward')->nullable();
            $table->text('address')->nullable();
            $table->string('landmark')->nullable();
            
            // Source
            $table->string('source')->nullable(); // WhatsApp, Referral, etc.
            
            // Requirements
            $table->text('requirements')->nullable();
            $table->text('detailed_requirement')->nullable();
            
            // Opportunity
            $table->decimal('estimated_monthly_value', 15, 2)->default(0);
            $table->string('urgency')->default('Medium'); // High, Medium, Low
            $table->date('expected_purchase_date')->nullable();
            
            // Sales Tracking
            $table->foreignId('sales_officer_id')->nullable()->constrained('users');
            $table->date('last_contacted_at')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->string('buying_stage')->default('Inquiry');
            $table->string('payment_terms')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
