<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Models\Booking;
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
     * API endpoint to query available vehicles and detailed conflict status for a given date/time window.
     */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'start_time' => ['required', 'date'],
            'end_time'   => ['required', 'date', 'after:start_time'],
        ]);

        $startTime = $request->input('start_time');
        $endTime   = $request->input('end_time');

        $blockedStatuses = [
            Booking::STATUS_APPROVED,
            Booking::STATUS_ASSIGNED,
            Booking::STATUS_IN_PROGRESS,
        ];

        // Eager load active bookings that overlap with requested time window
        $vehicles = Vehicle::with([
            'bookings' => function ($q) use ($blockedStatuses, $startTime, $endTime) {
                $q->whereIn('status', $blockedStatuses)
                  ->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime)
                  ->with(['employee.department', 'driver.user'])
                  ->orderBy('start_time');
            }
        ])->orderBy('brand')->get();

        $availableVehicles   = [];
        $unavailableVehicles = [];

        foreach ($vehicles as $vehicle) {
            $overlappingBooking = $vehicle->bookings->first();

            if ($vehicle->current_status === Vehicle::STATUS_MAINTENANCE) {
                $unavailableVehicles[] = [
                    'id'               => $vehicle->id,
                    'plate_number'     => $vehicle->plate_number,
                    'brand'            => $vehicle->brand,
                    'model'            => $vehicle->model,
                    'capacity'         => $vehicle->capacity,
                    'photo'            => $vehicle->photo,
                    'current_km'       => $vehicle->current_km,
                    'current_status'   => $vehicle->current_status,
                    'is_available'     => false,
                    'reason'           => 'maintenance',
                    'status_label'     => 'Sedang Perawatan / Maintenance',
                    'conflict_booking' => null,
                ];
            } elseif ($overlappingBooking) {
                $statusLabel = match ($overlappingBooking->status) {
                    Booking::STATUS_IN_PROGRESS => 'Sedang Dalam Perjalanan (Trip In Progress)',
                    Booking::STATUS_ASSIGNED    => 'Sudah Ditugaskan ke Driver',
                    Booking::STATUS_APPROVED    => 'Jadwal Telah Dibooking (Approved)',
                    default                     => 'Jadwal Terisi',
                };

                $unavailableVehicles[] = [
                    'id'               => $vehicle->id,
                    'plate_number'     => $vehicle->plate_number,
                    'brand'            => $vehicle->brand,
                    'model'            => $vehicle->model,
                    'capacity'         => $vehicle->capacity,
                    'photo'            => $vehicle->photo,
                    'current_km'       => $vehicle->current_km,
                    'current_status'   => $vehicle->current_status,
                    'is_available'     => false,
                    'reason'           => 'conflicted_booking',
                    'status_label'     => $statusLabel,
                    'conflict_booking' => [
                        'id'              => $overlappingBooking->id,
                        'destination'     => $overlappingBooking->destination,
                        'purpose'         => $overlappingBooking->purpose,
                        'start_time'      => $overlappingBooking->start_time?->toIso8601String(),
                        'end_time'        => $overlappingBooking->end_time?->toIso8601String(),
                        'employee_name'   => $overlappingBooking->employee?->name ?? 'Karyawan',
                        'department_name' => $overlappingBooking->employee?->department?->name ?? 'Umum',
                        'driver_name'     => $overlappingBooking->driver?->user?->name,
                        'status'          => $overlappingBooking->status,
                    ],
                ];
            } else {
                $availableVehicles[] = [
                    'id'             => $vehicle->id,
                    'plate_number'   => $vehicle->plate_number,
                    'brand'          => $vehicle->brand,
                    'model'          => $vehicle->model,
                    'capacity'       => $vehicle->capacity,
                    'photo'          => $vehicle->photo,
                    'current_km'     => $vehicle->current_km,
                    'current_status' => $vehicle->current_status,
                    'is_available'   => true,
                    'status_label'   => 'Tersedia untuk Jadwal Ini',
                ];
            }
        }

        return response()->json([
            'available_vehicles'   => $availableVehicles,
            'unavailable_vehicles' => $unavailableVehicles,
            'has_available'        => count($availableVehicles) > 0,
            'recommended_vehicle'  => $availableVehicles[0] ?? null,
        ]);
    }
}
