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
        Schema::create('kpi_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The staff being reviewed
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade'); // The reviewer
            $table->text('note');
            $table->string('type')->default('observation'); // observation, warning, achievement
            $table->boolean('is_visible_to_staff')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_notes');
    }
};
