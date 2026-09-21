<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\TripLog;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $picRole      = Role::where('slug', Role::PIC)->firstOrFail();
        $employeeRole = Role::where('slug', Role::EMPLOYEE)->firstOrFail();
        $driverRole   = Role::where('slug', Role::DRIVER)->firstOrFail();

        $hrDept  = Department::where('code', 'HR')->firstOrFail();
        $opsDept = Department::where('code', 'OPS')->firstOrFail();

        // 1. PIC User
        $picUser = User::firstOrCreate(
            ['email' => 'pic.manager@fleet.local'],
            [
                'name'          => 'Hendra Wijaya (PIC)',
                'password'      => Hash::make('Password@123'),
                'role_id'       => $picRole->id,
                'department_id' => $hrDept->id,
                'manager_id'    => null,
                'phone'         => '081234567890',
            ]
        );

        // 2. Employee User (reports to PIC)
        $employeeUser = User::firstOrCreate(
            ['email' => 'budi.santoso@fleet.local'],
            [
                'name'          => 'Budi Santoso',
                'password'      => Hash::make('Password@123'),
                'role_id'       => $employeeRole->id,
                'department_id' => $hrDept->id,
                'manager_id'    => $picUser->id,
                'phone'         => '081298765432',
            ]
        );

        // 3. Driver Users & Driver Profiles
        $driverUser1 = User::firstOrCreate(
            ['email' => 'joko.driver@fleet.local'],
            [
                'name'          => 'Joko Susilo',
                'password'      => Hash::make('Password@123'),
                'role_id'       => $driverRole->id,
                'department_id' => $opsDept->id,
                'manager_id'    => null,
                'phone'         => '081311223344',
            ]
        );

        $driver1 = Driver::firstOrCreate(
            ['user_id' => $driverUser1->id],
            [
                'license_number' => 'SIM-A-987654321',
                'license_expiry' => Carbon::now()->addYears(3),
                'is_active'      => true,
            ]
        );

        $driverUser2 = User::firstOrCreate(
            ['email' => 'agus.driver@fleet.local'],
            [
                'name'          => 'Agus Pratama',
                'password'      => Hash::make('Password@123'),
                'role_id'       => $driverRole->id,
                'department_id' => $opsDept->id,
                'manager_id'    => null,
                'phone'         => '081355667788',
            ]
        );

        $driver2 = Driver::firstOrCreate(
            ['user_id' => $driverUser2->id],
            [
                'license_number' => 'SIM-B1-123456789',
                'license_expiry' => Carbon::now()->addYears(2),
                'is_active'      => true,
            ]
        );

        // 4. Vehicles
        $zenix = Vehicle::firstOrCreate(
            ['plate_number' => 'B 1024 KCC'],
            [
                'brand'          => 'Toyota',
                'model'          => 'Innova Zenix Hybrid',
                'color'          => 'Platinum White Pearl',
                'year'           => 2024,
                'capacity'       => 7,
                'fuel_type'      => 'hybrid',
                'current_km'     => 12500,
                'current_status' => Vehicle::STATUS_AVAILABLE,
            ]
        );

        $veloz = Vehicle::firstOrCreate(
            ['plate_number' => 'B 2048 KCC'],
            [
                'brand'          => 'Toyota',
                'model'          => 'Avanza Veloz Q CVT',
                'color'          => 'Silver Metallic',
                'year'           => 2023,
                'capacity'       => 7,
                'fuel_type'      => 'petrol',
                'current_km'     => 28400,
                'current_status' => Vehicle::STATUS_AVAILABLE,
            ]
        );

        $ioniq = Vehicle::firstOrCreate(
            ['plate_number' => 'B 3072 KCC'],
            [
                'brand'          => 'Hyundai',
                'model'          => 'Ioniq 5 Long Range',
                'color'          => 'Gravity Gold Matte',
                'year'           => 2024,
                'capacity'       => 5,
                'fuel_type'      => 'electric',
                'current_km'     => 7800,
                'current_status' => Vehicle::STATUS_AVAILABLE,
            ]
        );

        $pajero = Vehicle::firstOrCreate(
            ['plate_number' => 'B 4096 KCC'],
            [
                'brand'          => 'Mitsubishi',
                'model'          => 'Pajero Sport Dakar',
                'color'          => 'Deep Black Mica',
                'year'           => 2022,
                'capacity'       => 7,
                'fuel_type'      => 'diesel',
                'current_km'     => 54100,
                'current_status' => Vehicle::STATUS_MAINTENANCE,
            ]
        );

        // 5. Sample Bookings (FSM scenarios)
        // Booking 1: Pending Approval (for PIC)
        Booking::firstOrCreate(
            ['destination' => 'PT Astra Honda Motor, Cikarang'],
            [
                'employee_id'      => $employeeUser->id,
                'manager_id'       => $picUser->id,
                'vehicle_id'       => $zenix->id,
                'driver_id'        => null,
                'start_time'       => Carbon::tomorrow()->setHour(9)->setMinute(0),
                'end_time'         => Carbon::tomorrow()->setHour(17)->setMinute(0),
                'purpose'          => 'Quarterly executive partnership and fleet audit review meeting.',
                'passenger_count'  => 4,
                'status'           => Booking::STATUS_PENDING_APPROVAL,
            ]
        );

        // Booking 2: Assigned Trip (waiting for Driver Joko to start)
        Booking::firstOrCreate(
            ['destination' => 'Kementerian Perhubungan RI, Jakarta Pusat'],
            [
                'employee_id'      => $employeeUser->id,
                'manager_id'       => $picUser->id,
                'vehicle_id'       => $veloz->id,
                'driver_id'        => $driver1->id,
                'start_time'       => Carbon::now()->addHours(2),
                'end_time'         => Carbon::now()->addHours(8),
                'purpose'          => 'Corporate license permit renewal and administrative legal filing.',
                'passenger_count'  => 2,
                'status'           => Booking::STATUS_ASSIGNED,
            ]
        );

        // Booking 3: Completed Trip with Post-Trip Log
        $pastBooking = Booking::firstOrCreate(
            ['destination' => 'Bandara Internasional Soekarno-Hatta (Terminal 3)'],
            [
                'employee_id'      => $employeeUser->id,
                'manager_id'       => $picUser->id,
                'vehicle_id'       => $ioniq->id,
                'driver_id'        => $driver2->id,
                'start_time'       => Carbon::yesterday()->setHour(6)->setMinute(0),
                'end_time'         => Carbon::yesterday()->setHour(14)->setMinute(0),
                'purpose'          => 'VIP Guest delegation airport pickup and executive transit.',
                'passenger_count'  => 3,
                'status'           => Booking::STATUS_COMPLETED,
            ]
        );

        TripLog::firstOrCreate(
            ['booking_id' => $pastBooking->id],
            [
                'start_km'         => 7710,
                'end_km'           => 7800,
                'fuel_receipt_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&q=80&w=600',
                'notes'            => 'Pickup smooth without delay. EV charged at SPKLU Terminal 3.',
            ]
        );
    }
}
