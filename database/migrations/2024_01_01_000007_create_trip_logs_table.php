<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('booking_id')
                  ->unique() // one log per booking
                  ->constrained('bookings')
                  ->cascadeOnDelete();

            $table->unsignedInteger('start_km');
            $table->unsignedInteger('end_km');

            // PostgreSQL stored/computed column — zero-cost reads
            $table->unsignedInteger('total_km')->storedAs('"end_km" - "start_km"');

            $table->string('fuel_receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_logs');
    }
};
