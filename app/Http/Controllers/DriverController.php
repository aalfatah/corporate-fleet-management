<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriverRequest;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers.
     */
    public function index(Request $request): Response
    {
        $query = Driver::with(['user.department']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            })->orWhere('license_number', 'ilike', "%{$search}%");
        }

        $drivers = $query->latest()->paginate(15)->withQueryString();

        // Eligible users for driver registration (users with driver role without driver profile yet)
        $eligibleUsers = User::whereHas('role', function ($q) {
            $q->where('slug', Role::DRIVER);
        })->whereDoesntHave('driver')->select('id', 'name', 'email')->get();

        return Inertia::render('Drivers/Index', [
            'drivers'       => $drivers,
            'eligibleUsers' => $eligibleUsers,
            'filters'       => $request->only(['is_active', 'search']),
        ]);
    }

    /**
     * Store a newly created driver.
     */
    public function store(StoreDriverRequest $request): RedirectResponse
    {
        Driver::create($request->validated());

        return redirect()->route('drivers.index')
            ->with('success', 'Driver profile created successfully.');
    }

    /**
     * Display the specified driver.
     */
    public function show(Driver $driver): Response
    {
        $driver->load([
            'user.department',
            'bookings' => fn ($q) => $q->with(['vehicle', 'employee'])->latest()->take(10),
        ]);

        return Inertia::render('Drivers/Show', [
            'driver' => $driver,
        ]);
    }

    /**
     * Update the specified driver.
     */
    public function update(StoreDriverRequest $request, Driver $driver): RedirectResponse
    {
        $driver->update($request->validated());

        return redirect()->route('drivers.show', $driver->id)
            ->with('success', 'Driver details updated.');
    }

    /**
     * Remove the specified driver from storage (Soft delete).
     */
    public function destroy(Driver $driver): RedirectResponse
    {
        $driver->delete();

        return redirect()->route('drivers.index')
            ->with('success', 'Driver profile deleted.');
    }
}
