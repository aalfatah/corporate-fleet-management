<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FleetWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (User::count() === 0) {
            $this->seed(\Database\Seeders\DatabaseSeeder::class);
        }
    }

    public function test_full_fsm_booking_lifecycle_and_anti_double_booking(): void
    {
        $picRole      = Role::where('slug', Role::PIC)->first();
        $employeeRole = Role::where('slug', Role::EMPLOYEE)->first();
        $driverRole   = Role::where('slug', Role::DRIVER)->first();
        $adminRole    = Role::where('slug', Role::SUPER_ADMIN)->first();

        $admin = User::where('role_id', $adminRole->id)->first();
        $pic   = User::where('role_id', $picRole->id)->first();
        $employee = User::where('role_id', $employeeRole->id)->first();
        $driver = Driver::with('user')->first();
        $driverUser = $driver->user;
        $vehicle = Vehicle::where('plate_number', 'B 1024 KCC')->first() ?? Vehicle::first();

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

    public function test_pic_can_assign_driver_and_driver_receives_realtime_notification(): void
    {
        $picRole = Role::where('slug', Role::PIC)->first();
        $employeeRole = Role::where('slug', Role::EMPLOYEE)->first();
        $pic = User::where('role_id', $picRole->id)->first();
        $employee = User::where('role_id', $employeeRole->id)->first();
        $driver = Driver::with('user')->first();
        $driverUser = $driver->user;
        $vehicle = Vehicle::where('plate_number', 'B 5678 KCC')->first() ?? Vehicle::first();

        $start = Carbon::now()->addDays(10)->setHour(8)->setMinute(0)->toDateTimeString();
        $end   = Carbon::now()->addDays(10)->setHour(16)->setMinute(0)->toDateTimeString();

        // 1. Employee creates booking
        $booking = Booking::create([
            'employee_id'     => $employee->id,
            'manager_id'      => $pic->id,
            'vehicle_id'      => $vehicle->id,
            'start_time'      => $start,
            'end_time'        => $end,
            'destination'     => 'KCC Surabaya Hub',
            'purpose'         => 'Logistics audit.',
            'passenger_count' => 2,
            'status'          => Booking::STATUS_PENDING_APPROVAL,
        ]);

        // 2. PIC Approves Booking
        $this->actingAs($pic)->post("/bookings/{$booking->id}/approve")->assertRedirect();
        $booking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $booking->status);

        // 3. PIC views booking and gets available drivers
        $showResponse = $this->actingAs($pic)->get("/bookings/{$booking->id}");
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page
            ->component('Bookings/Show')
            ->has('availableDrivers')
        );

        // 4. PIC Assigns Driver & Car (Must succeed without permission denied)
        $assignResponse = $this->actingAs($pic)->post("/bookings/{$booking->id}/assign", [
            'driver_id'  => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);
        $assignResponse->assertSessionHasNoErrors();
        $assignResponse->assertRedirect();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_ASSIGNED, $booking->status);
        $this->assertEquals($driver->id, $booking->driver_id);

        // 5. Verify Driver User received database notification
        $driverUser->refresh();
        $notification = $driverUser->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertEquals($booking->id, $notification->data['booking_id']);
        $this->assertEquals('trip_assigned', $notification->data['type']);
        $this->assertEquals('KCC Surabaya Hub', $notification->data['destination']);

        // 6. Verify Driver unread notifications API
        $unreadResponse = $this->actingAs($driverUser)->getJson('/notifications/unread');
        $unreadResponse->assertOk();
        $unreadResponse->assertJsonStructure(['unread_count', 'notifications']);
        $this->assertGreaterThanOrEqual(1, $unreadResponse->json('unread_count'));

        // 7. Verify Driver active assignment endpoint for pop-up alert
        $activeAssignmentResponse = $this->actingAs($driverUser)->getJson('/api/driver/active-assignment');
        $activeAssignmentResponse->assertOk();
        $this->assertNotNull($activeAssignmentResponse->json('assignment'));
        $this->assertEquals($booking->id, $activeAssignmentResponse->json('assignment.id'));
    }

    public function test_booking_creation_without_pic_and_conflict_details_endpoint(): void
    {
        $employeeRole = Role::where('slug', Role::EMPLOYEE)->first();
        $picRole      = Role::where('slug', Role::PIC)->first();
        $employee = User::where('role_id', $employeeRole->id)->first();
        $pic = User::where('role_id', $picRole->id)->first();
        $vehicle = Vehicle::first();

        $start = Carbon::now()->addDays(20)->setHour(9)->setMinute(0)->toDateTimeString();
        $end   = Carbon::now()->addDays(20)->setHour(17)->setMinute(0)->toDateTimeString();

        // 1. Employee creates booking WITHOUT specifying manager_id
        $response = $this->actingAs($employee)->post('/bookings', [
            'vehicle_id'      => $vehicle->id,
            'start_time'      => $start,
            'end_time'        => $end,
            'destination'     => 'KCC Bandung Branch',
            'purpose'         => 'Quarterly strategy meeting.',
            'passenger_count' => 3,
        ]);
        $response->assertRedirect();

        $booking = Booking::where('destination', 'KCC Bandung Branch')->latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals(Booking::STATUS_PENDING_APPROVAL, $booking->status);

        // 2. Any PIC can approve without constraint
        $approveResponse = $this->actingAs($pic)->post("/bookings/{$booking->id}/approve");
        $approveResponse->assertRedirect();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $booking->status);
        $this->assertEquals($pic->id, $booking->manager_id);

        // 3. Test /api/vehicles/available endpoint reports this vehicle as conflicted
        $availabilityResponse = $this->actingAs($employee)->getJson(
            "/api/vehicles/available?start_time=" . urlencode($start) . "&end_time=" . urlencode($end)
        );
        $availabilityResponse->assertOk();
        $availabilityResponse->assertJsonStructure([
            'available_vehicles',
            'unavailable_vehicles',
            'has_available',
        ]);

        $unavailable = collect($availabilityResponse->json('unavailable_vehicles'));
        $conflictedCar = $unavailable->firstWhere('id', $vehicle->id);
        $this->assertNotNull($conflictedCar);
        $this->assertFalse($conflictedCar['is_available']);
        $this->assertEquals('conflicted_booking', $conflictedCar['reason']);
        $this->assertEquals('KCC Bandung Branch', $conflictedCar['conflict_booking']['destination']);
        $this->assertEquals($employee->name, $conflictedCar['conflict_booking']['employee_name']);
    }

    public function test_reassign_driver_to_another_driver_successfully(): void
    {
        $picRole      = Role::where('slug', Role::PIC)->first();
        $employeeRole = Role::where('slug', Role::EMPLOYEE)->first();
        $pic = User::where('role_id', $picRole->id)->first();
        $employee = User::where('role_id', $employeeRole->id)->first();

        $drivers = Driver::with('user')->take(2)->get();
        $this->assertGreaterThanOrEqual(2, $drivers->count());
        $driver1 = $drivers[0];
        $driver2 = $drivers[1];
        $vehicle = Vehicle::first();

        $start = Carbon::now()->addDays(15)->setHour(9)->setMinute(0)->toDateTimeString();
        $end   = Carbon::now()->addDays(15)->setHour(17)->setMinute(0)->toDateTimeString();

        // 1. Create and approve booking
        $booking = Booking::create([
            'employee_id'     => $employee->id,
            'manager_id'      => $pic->id,
            'vehicle_id'      => $vehicle->id,
            'start_time'      => $start,
            'end_time'        => $end,
            'destination'     => 'KCC Semarang Logistics',
            'purpose'         => 'Material inspection.',
            'passenger_count' => 2,
            'status'          => Booking::STATUS_APPROVED,
        ]);

        // 2. Initial assignment to Driver 1
        $this->actingAs($pic)->post("/bookings/{$booking->id}/assign", [
            'driver_id' => $driver1->id,
        ])->assertRedirect();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_ASSIGNED, $booking->status);
        $this->assertEquals($driver1->id, $booking->driver_id);

        // 3. Reassign to Driver 2 while booking is in 'assigned' status
        $reassignResponse = $this->actingAs($pic)->post("/bookings/{$booking->id}/assign", [
            'driver_id' => $driver2->id,
        ]);
        $reassignResponse->assertRedirect();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_ASSIGNED, $booking->status);
        $this->assertEquals($driver2->id, $booking->driver_id, 'Driver must be updated to Driver 2 upon reassignment');

        // 4. Verify Driver 2 received assignment notification
        $notification = $driver2->user->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('trip_assigned', $notification->data['type']);
        $this->assertEquals($booking->id, $notification->data['booking_id']);
    }
}
