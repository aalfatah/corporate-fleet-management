<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Tests\TestCase;

class FleetWorkflowTest extends TestCase
{
    public function test_full_fsm_booking_lifecycle_and_anti_double_booking(): void
    {
        $picRole      = Role::where('slug', Role::PIC)->first();
        $employeeRole = Role::where('slug', Role::EMPLOYEE)->first();
        $driverRole   = Role::where('slug', Role::DRIVER)->first();
        $adminRole    = Role::where('slug', Role::SUPER_ADMIN)->first();

        $admin = User::where('email', 'superadmin@fleet.local')->first();
        $pic   = User::where('email', 'pic.manager@fleet.local')->first();
        $employee = User::where('email', 'budi.santoso@fleet.local')->first();
        $driverUser = User::where('email', 'joko.driver@fleet.local')->first();
        $driver = Driver::where('user_id', $driverUser->id)->first();
        $vehicle = Vehicle::where('plate_number', 'B 1024 KCC')->first();

        $start = Carbon::now()->addDays(5)->setHour(9)->setMinute(0)->toDateTimeString();
        $end   = Carbon::now()->addDays(5)->setHour(17)->setMinute(0)->toDateTimeString();

        // 1. Employee creates booking
        $response = $this->actingAs($employee)->post('/bookings', [
            'vehicle_id'      => $vehicle->id,
            'manager_id'      => $pic->id,
            'start_time'      => $start,
            'end_time'        => $end,
            'destination'     => 'KCC Factory Batang Plant',
            'purpose'         => 'Plant operations review and QA inspection.',
            'passenger_count' => 3,
        ]);

        $response->assertRedirect();
        $booking = Booking::where('destination', 'KCC Factory Batang Plant')->latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals(Booking::STATUS_PENDING_APPROVAL, $booking->status);

        // 2. PIC Approves Booking
        $approveResponse = $this->actingAs($pic)->post("/bookings/{$booking->id}/approve");
        $approveResponse->assertRedirect();
        $booking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $booking->status);

        // 3. Concurrency / Anti-double booking test:
        // Another employee tries to book the same vehicle in overlapping time
        $conflictResponse = $this->actingAs($employee)->post('/bookings', [
            'vehicle_id'      => $vehicle->id,
            'manager_id'      => $pic->id,
            'start_time'      => Carbon::now()->addDays(5)->setHour(10)->toDateTimeString(),
            'end_time'        => Carbon::now()->addDays(5)->setHour(14)->toDateTimeString(),
            'destination'     => 'Jakarta Meeting',
            'purpose'         => 'Sales presentation.',
            'passenger_count' => 2,
        ]);

        $conflictResponse->assertSessionHasErrors(['vehicle_id']);

        // 4. Super Admin Assigns Driver
        $assignResponse = $this->actingAs($admin)->post("/bookings/{$booking->id}/assign", [
            'driver_id'  => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $assignResponse->assertRedirect();
        $booking->refresh();
        $this->assertEquals(Booking::STATUS_ASSIGNED, $booking->status);
        $this->assertEquals($driver->id, $booking->driver_id);

        // 5. Driver Starts Trip
        $startTripResponse = $this->actingAs($driverUser)->post("/trips/{$booking->id}/start");
        $startTripResponse->assertRedirect();
        $booking->refresh();
        $this->assertEquals(Booking::STATUS_IN_PROGRESS, $booking->status);

        // 6. Driver Completes Trip & Submits KM Log
        $startKm = $vehicle->current_km;
        $endKm   = $startKm + 85;

        $completeTripResponse = $this->actingAs($driverUser)->post("/trips/{$booking->id}/complete", [
            'start_km'         => $startKm,
            'end_km'           => $endKm,
            'notes'            => 'Return safely to pool. Clean vehicle.',
            'fuel_receipt_url' => 'https://example.com/receipt.jpg',
        ]);
        $completeTripResponse->assertRedirect();

        $booking->refresh();
        $vehicle->refresh();

        $this->assertEquals(Booking::STATUS_COMPLETED, $booking->status);
        $this->assertEquals($endKm, $vehicle->current_km);
        $this->assertEquals(Vehicle::STATUS_AVAILABLE, $vehicle->current_status);
        $this->assertNotNull($booking->tripLog);
        $this->assertEquals(85, $booking->tripLog->total_km);
    }
}
