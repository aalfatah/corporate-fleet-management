<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard tailored for the authenticated user's role.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load(['role', 'department', 'driver']);
        $roleSlug = $user->role?->slug;

        $stats = [];
        $activeBookings = [];
        $pendingApprovals = [];
        $driverCurrentTrip = null;

        if ($roleSlug === 'super_admin') {
            $stats = [
                'total_vehicles'     => Vehicle::count(),
                'available_vehicles' => Vehicle::where('current_status', Vehicle::STATUS_AVAILABLE)->count(),
                'in_use_vehicles'    => Vehicle::where('current_status', Vehicle::STATUS_IN_USE)->count(),
                'maintenance_vehicles' => Vehicle::where('current_status', Vehicle::STATUS_MAINTENANCE)->count(),
                'active_drivers'     => Driver::where('is_active', true)->count(),
                'pending_approval'   => Booking::where('status', Booking::STATUS_PENDING_APPROVAL)->count(),
                'in_progress_trips'  => Booking::where('status', Booking::STATUS_IN_PROGRESS)->count(),
                'completed_trips'    => Booking::where('status', Booking::STATUS_COMPLETED)->count(),
            ];

            $activeBookings = Booking::with(['employee.department', 'manager', 'vehicle', 'driver.user'])
                ->latest()
                ->take(8)
                ->get();

        } elseif ($roleSlug === 'pic') {
            $stats = [
                'pending_my_approval' => Booking::pendingApproval()->count(),
                'approved_by_me'      => Booking::where('status', Booking::STATUS_APPROVED)->count(),
                'in_progress'         => Booking::where('status', Booking::STATUS_IN_PROGRESS)->count(),
                'completed'           => Booking::where('status', Booking::STATUS_COMPLETED)->count(),
            ];

            $pendingApprovals = Booking::pendingApproval()
                ->with(['employee.department', 'vehicle'])
                ->latest()
                ->get();

            $activeBookings = Booking::with(['employee.department', 'vehicle', 'driver.user'])
                ->latest()
                ->take(5)
                ->get();

        } elseif ($roleSlug === 'driver') {
            $driver = $user->driver;
            $driverId = $driver?->id;

            if ($driverId) {
                $driverCurrentTrip = Booking::where('driver_id', $driverId)
                    ->where('status', Booking::STATUS_IN_PROGRESS)
                    ->with(['employee.department', 'vehicle'])
                    ->first();

                $assignedTrips = Booking::where('driver_id', $driverId)
                    ->where('status', Booking::STATUS_ASSIGNED)
                    ->with(['employee.department', 'vehicle'])
                    ->orderBy('start_time')
                    ->get();

                $stats = [
                    'assigned_count'   => $assignedTrips->count(),
                    'has_active_trip'  => (bool) $driverCurrentTrip,
                    'completed_trips'  => Booking::where('driver_id', $driverId)->where('status', Booking::STATUS_COMPLETED)->count(),
                ];

                $activeBookings = $assignedTrips;
            }

        } else {
            // Employee
            $stats = [
                'my_pending'     => Booking::forEmployee($user->id)->pendingApproval()->count(),
                'my_active'      => Booking::forEmployee($user->id)->active()->count(),
                'my_completed'   => Booking::forEmployee($user->id)->where('status', Booking::STATUS_COMPLETED)->count(),
                'available_cars' => Vehicle::where('current_status', Vehicle::STATUS_AVAILABLE)->count(),
            ];

            $activeBookings = Booking::forEmployee($user->id)
                ->with(['vehicle', 'driver.user', 'manager'])
                ->latest()
                ->take(5)
                ->get();
        }

        return Inertia::render('Dashboard/Index', [
            'stats'             => $stats,
            'activeBookings'    => $activeBookings,
            'pendingApprovals'  => $pendingApprovals,
            'driverCurrentTrip' => $driverCurrentTrip,
        ]);
    }
}
