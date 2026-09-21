<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles.
     */
    public function index(Request $request): Response
    {
        $query = Vehicle::query();

        if ($status = $request->input('status')) {
            $query->where('current_status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'ilike', "%{$search}%")
                  ->orWhere('brand', 'ilike', "%{$search}%")
                  ->orWhere('model', 'ilike', "%{$search}%");
            });
        }

        $vehicles = $query->orderBy('plate_number')->paginate(12)->withQueryString();

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters'  => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('vehicles', 'public');
        }

        Vehicle::create($data);

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicle registered successfully.');
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle): Response
    {
        $vehicle->load([
            'bookings' => fn ($q) => $q->with(['employee', 'driver.user'])->latest()->take(10),
        ]);

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(StoreVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($vehicle->photo) {
                Storage::disk('public')->delete($vehicle->photo);
            }
            $data['photo'] = $request->file('photo')->store('vehicles', 'public');
        }

        $vehicle->update($data);

        return redirect()->route('vehicles.show', $vehicle->id)
            ->with('success', 'Vehicle updated successfully.');
    }

    /**
     * Remove the specified vehicle from storage (Soft delete).
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicle removed successfully.');
    }

    /**
     * API endpoint to query available vehicles for a given date/time window.
     */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'start_time' => ['required', 'date'],
            'end_time'   => ['required', 'date', 'after:start_time'],
        ]);

        $vehicles = Vehicle::availableFor(
            $request->input('start_time'),
            $request->input('end_time')
        )->get();

        return response()->json([
            'available_vehicles' => $vehicles,
        ]);
    }
}
