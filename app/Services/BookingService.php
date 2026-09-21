<?php

namespace App\Services;

use App\Events\BookingStatusUpdated;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\TripLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Create a new booking request in pending_approval status.
     *
     * Uses pessimistic locking (lockForUpdate) on the requested vehicle row inside
     * a DB transaction to prevent concurrent double-booking (anti-TOCTOU).
     */
    public function createBooking(User $employee, array $data): Booking
    {
        return DB::transaction(function () use ($employee, $data) {
            // 1. Lock the requested vehicle row
            $vehicle = Vehicle::where('id', $data['vehicle_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($vehicle->current_status === Vehicle::STATUS_MAINTENANCE) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'This vehicle is currently undergoing maintenance.',
                ]);
            }

            // 2. Lock & re-verify that there are no overlapping active bookings for this vehicle
            $hasConflict = Booking::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereIn('status', [
                    Booking::STATUS_APPROVED,
                    Booking::STATUS_ASSIGNED,
                    Booking::STATUS_IN_PROGRESS,
                ])
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'The selected vehicle is not available for the requested time slot.',
                ]);
            }

            // 3. Resolve manager/approver ID
            $managerId = $data['manager_id'] ?? $employee->manager_id;
            if (! $managerId) {
                throw ValidationException::withMessages([
                    'manager_id' => 'No approving manager is configured for this employee.',
                ]);
            }

            // 4. Create booking
            $booking = Booking::create([
                'employee_id'     => $employee->id,
                'manager_id'      => $managerId,
                'vehicle_id'      => $vehicle->id,
                'driver_id'       => null,
                'start_time'      => $data['start_time'],
                'end_time'        => $data['end_time'],
                'destination'     => $data['destination'],
                'purpose'         => $data['purpose'],
                'passenger_count' => $data['passenger_count'] ?? 1,
                'status'          => Booking::STATUS_PENDING_APPROVAL,
            ]);

            event(new BookingStatusUpdated($booking));

            return $booking;
        });
    }

    /**
     * Approve a pending booking request (PIC/Approver action).
     * Status: pending_approval -> approved
     */
    public function approveBooking(Booking $booking, User $approver): Booking
    {
        return DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanTransition($lockedBooking, Booking::STATUS_APPROVED);

            $lockedBooking->update([
                'status' => Booking::STATUS_APPROVED,
            ]);

            event(new BookingStatusUpdated($lockedBooking));

            return $lockedBooking;
        });
    }

    /**
     * Reject a pending booking request (PIC/Approver action).
     * Status: pending_approval -> rejected
     */
    public function rejectBooking(Booking $booking, User $approver, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $reason) {
            $lockedBooking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanTransition($lockedBooking, Booking::STATUS_REJECTED);

            $lockedBooking->update([
                'status'           => Booking::STATUS_REJECTED,
                'rejection_reason' => $reason,
            ]);

            event(new BookingStatusUpdated($lockedBooking));

            return $lockedBooking;
        });
    }

    /**
     * Assign a driver and confirm vehicle allocation (Admin action).
     * Status: approved -> assigned
     *
     * Locks both the booking and the driver record to prevent double-assignment.
     */
    public function assignDriverAndVehicle(Booking $booking, string $driverId, ?string $vehicleId = null): Booking
    {
        return DB::transaction(function () use ($booking, $driverId, $vehicleId) {
            $lockedBooking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanTransition($lockedBooking, Booking::STATUS_ASSIGNED);

            // 1. Lock driver and verify availability
            $driver = Driver::where('id', $driverId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $driverConflict = Booking::query()
                ->where('driver_id', $driver->id)
                ->whereIn('status', [
                    Booking::STATUS_ASSIGNED,
                    Booking::STATUS_IN_PROGRESS,
                ])
                ->where('start_time', '<', $lockedBooking->end_time)
                ->where('end_time', '>', $lockedBooking->start_time)
                ->where('id', '!=', $lockedBooking->id)
                ->lockForUpdate()
                ->exists();

            if ($driverConflict) {
                throw ValidationException::withMessages([
                    'driver_id' => 'The selected driver is already assigned to another active trip during this time slot.',
                ]);
            }

            // 2. Lock vehicle (use newly provided vehicleId if changed, otherwise existing)
            $finalVehicleId = $vehicleId ?? $lockedBooking->vehicle_id;
            $vehicle = Vehicle::where('id', $finalVehicleId)
                ->lockForUpdate()
                ->firstOrFail();

            $vehicleConflict = Booking::query()
                ->where('vehicle_id', $vehicle->id)
                ->whereIn('status', [
                    Booking::STATUS_ASSIGNED,
                    Booking::STATUS_IN_PROGRESS,
                ])
                ->where('start_time', '<', $lockedBooking->end_time)
                ->where('end_time', '>', $lockedBooking->start_time)
                ->where('id', '!=', $lockedBooking->id)
                ->lockForUpdate()
                ->exists();

            if ($vehicleConflict) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'The selected vehicle is already assigned to another booking during this time slot.',
                ]);
            }

            $lockedBooking->update([
                'driver_id'  => $driver->id,
                'vehicle_id' => $vehicle->id,
                'status'     => Booking::STATUS_ASSIGNED,
            ]);

            $vehicle->update(['current_status' => Vehicle::STATUS_IN_USE]);

            event(new BookingStatusUpdated($lockedBooking));

            return $lockedBooking;
        });
    }

    /**
     * Driver starts trip.
     * Status: assigned -> in_progress
     */
    public function startTrip(Booking $booking, Driver $driver): Booking
    {
        return DB::transaction(function () use ($booking, $driver) {
            $lockedBooking = Booking::where('id', $booking->id)
                ->where('driver_id', $driver->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanTransition($lockedBooking, Booking::STATUS_IN_PROGRESS);

            $lockedBooking->update([
                'status' => Booking::STATUS_IN_PROGRESS,
            ]);

            if ($lockedBooking->vehicle_id) {
                Vehicle::where('id', $lockedBooking->vehicle_id)
                    ->update(['current_status' => Vehicle::STATUS_IN_USE]);
            }

            event(new BookingStatusUpdated($lockedBooking));

            return $lockedBooking;
        });
    }

    /**
     * Driver finishes trip and submits trip logs.
     * Status: in_progress -> completed
     */
    public function completeTrip(Booking $booking, Driver $driver, array $logData): Booking
    {
        return DB::transaction(function () use ($booking, $driver, $logData) {
            $lockedBooking = Booking::where('id', $booking->id)
                ->where('driver_id', $driver->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanTransition($lockedBooking, Booking::STATUS_COMPLETED);

            // Lock vehicle to update current KM and reset status
            $vehicle = Vehicle::where('id', $lockedBooking->vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();

            TripLog::create([
                'booking_id'       => $lockedBooking->id,
                'start_km'         => $logData['start_km'],
                'end_km'           => $logData['end_km'],
                'fuel_receipt_url' => $logData['fuel_receipt_url'] ?? null,
                'notes'            => $logData['notes'] ?? null,
            ]);

            $vehicle->update([
                'current_km'     => $logData['end_km'],
                'current_status' => Vehicle::STATUS_AVAILABLE,
            ]);

            $lockedBooking->update([
                'status' => Booking::STATUS_COMPLETED,
            ]);

            event(new BookingStatusUpdated($lockedBooking->fresh(['vehicle', 'driver', 'employee', 'tripLog'])));

            return $lockedBooking;
        });
    }

    /**
     * Cancel a booking (allowed when status is pending_approval or approved).
     * Status: pending_approval | approved -> cancelled
     */
    public function cancelBooking(Booking $booking, User $user): Booking
    {
        return DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanTransition($lockedBooking, Booking::STATUS_CANCELLED);

            if ($lockedBooking->vehicle_id && $lockedBooking->isApproved()) {
                Vehicle::where('id', $lockedBooking->vehicle_id)
                    ->update(['current_status' => Vehicle::STATUS_AVAILABLE]);
            }

            $lockedBooking->update([
                'status' => Booking::STATUS_CANCELLED,
            ]);

            event(new BookingStatusUpdated($lockedBooking));

            return $lockedBooking;
        });
    }

    /**
     * Enforce strict Finite State Machine transitions.
     *
     * @throws ValidationException
     */
    protected function assertCanTransition(Booking $booking, string $newStatus): void
    {
        if (! $booking->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Invalid status transition: Cannot change booking status from [%s] to [%s].',
                    $booking->status,
                    $newStatus
                ),
            ]);
        }
    }
}
