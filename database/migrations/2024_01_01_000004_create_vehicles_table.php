<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('plate_number')->unique();
            $table->string('brand');
            $table->string('model');
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('capacity'); // passenger seats
            $table->string('fuel_type')->nullable(); // petrol, diesel, electric, hybrid
            $table->unsignedInteger('current_km')->default(0);

            // FSM status: available | in_use | maintenance
            $table->string('current_status')->default('available');

            $table->string('photo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('current_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
