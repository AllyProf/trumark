<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 30);
            $table->text('items');
            $table->string('delivery_method')->nullable();
            $table->text('address')->nullable();
            $table->decimal('estimated_total', 12, 2)->nullable();
            $table->json('estimate_breakdown')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_screenshot_path')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['phone', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_orders');
    }
};
