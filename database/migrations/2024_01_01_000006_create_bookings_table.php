<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Who made the request
            $table->foreignUuid('employee_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Who approved/rejected
            $table->foreignUuid('manager_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // The vehicle assigned (nullable until approved/assigned)
            $table->foreignUuid('vehicle_id')
                  ->nullable()
                  ->constrained('vehicles')
                  ->restrictOnDelete();

            // The driver assigned (nullable, strictly separate from vehicle)
            $table->foreignUuid('driver_id')
                  ->nullable()
                  ->constrained('drivers')
                  ->restrictOnDelete();

            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('destination');
            $table->text('purpose');
            $table->unsignedTinyInteger('passenger_count')->default(1);
            $table->text('rejection_reason')->nullable();

            /**
             * FSM Status Enum (stored as string for readability & portability):
             * pending_approval → approved → assigned → in_progress → completed
             *                 ↘ rejected
             *                 ↘ cancelled (employee cancels before assigned)
             */
            $table->string('status')->default('pending_approval');

            $table->timestamps();
            $table->softDeletes();

            // Compound index for the anti-double-booking availability check
            $table->index(['vehicle_id', 'status', 'start_time', 'end_time'], 'idx_booking_availability');
            $table->index('status');
            $table->index('employee_id');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
