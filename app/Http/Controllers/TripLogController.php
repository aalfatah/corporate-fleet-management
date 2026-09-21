<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteTripRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TripLogController extends Controller
{
    public function __construct(protected BookingService $bookingService)
    {
    }

    /**
     * Driver starts the assigned trip (assigned -> in_progress).
     */
    public function startTrip(Request $request, Booking $booking): RedirectResponse
    {
        $driver = $request->user()->driver;

        if (! $driver && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Only assigned drivers can start trips.');
        }

        // If driver starts, pass their driver profile. If Super Admin bypass, pass booking's assigned driver.
        $targetDriver = $driver ?? $booking->driver;
        if (! $targetDriver) {
            abort(422, 'No driver is assigned to this booking.');
        }

        $this->bookingService->startTrip($booking, $targetDriver);

        return redirect()->back()
            ->with('success', 'Trip started! Safe driving.');
    }

    /**
     * Driver completes the in-progress trip and submits log data.
     */
    public function completeTrip(CompleteTripRequest $request, Booking $booking): RedirectResponse
    {
        $driver = $request->user()->driver;
        $targetDriver = $driver ?? $booking->driver;

        if (! $targetDriver) {
            abort(422, 'No driver is assigned to this booking.');
        }

        $logData = $request->validated();

        // Handle receipt image file if uploaded as file
        if ($request->hasFile('receipt_image')) {
            $logData['fuel_receipt_url'] = $request->file('receipt_image')->store('receipts', 'public');
        }

        $this->bookingService->completeTrip($booking, $targetDriver, $logData);

        return redirect()->route('dashboard')
            ->with('success', 'Trip completed and vehicle log submitted successfully.');
    }
}
