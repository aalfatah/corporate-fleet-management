<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveBookingRequest;
use App\Http\Requests\AssignDriverRequest;
use App\Http\Requests\RejectBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService)
    {
    }

    /**
     * Display a listing of bookings filtered by the user's role and query params.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('role');
        $roleSlug = $user->role?->slug;

        $query = Booking::query()
            ->with(['employee.department', 'manager', 'vehicle', 'driver.user', 'tripLog']);

        // Role-based scope
        if ($roleSlug === 'employee') {
            $query->forEmployee($user->id);
        } elseif ($roleSlug === 'pic') {
            // PIC has fleet-wide approval & assignment oversight to prevent workflow bottlenecks
        } elseif ($roleSlug === 'driver') {
            $driver = $user->driver;
            if ($driver) {
                $query->forDriver($driver->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Optional status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings,
            'filters'  => $request->only(['status']),
        ]);
    }

    /**
     * Show the form for creating a new booking request.
     */
    public function create(Request $request): Response
    {
        $user = $request->user()->load('department');

        // Available managers (PIC or Super Admin)
        $managers = User::whereHas('role', function ($q) {
            $q->whereIn('slug', [Role::PIC, Role::SUPER_ADMIN]);
        })->select('id', 'name', 'email')->get();

        // Active vehicles
        $vehicles = Vehicle::where('current_status', '!=', Vehicle::STATUS_MAINTENANCE)
            ->select('id', 'plate_number', 'brand', 'model', 'capacity', 'current_status')
            ->get();

        return Inertia::render('Bookings/Create', [
            'managers' => $managers,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Store a newly created booking request.
     */
    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->createBooking(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('bookings.show', $booking->id)
            ->with('success', 'Booking request created successfully and submitted for approval.');
    }

    /**
     * Display the specified booking details.
     */
    public function show(Booking $booking): Response
    {
        $booking->load(['employee.department', 'manager', 'vehicle', 'driver.user', 'tripLog']);

        // Available active drivers for assignment (if user is Super Admin or PIC)
        $user = auth()->user();
        $availableDrivers = [];
        if (($user->isSuperAdmin() || $user->isPic()) && in_array($booking->status, [Booking::STATUS_APPROVED, Booking::STATUS_ASSIGNED])) {
            $availableDrivers = Driver::availableFor(
                $booking->start_time->toDateTimeString(),
                $booking->end_time->toDateTimeString(),
                $booking->id
            )->with('user')->get();
        }

        return Inertia::render('Bookings/Show', [
            'booking'          => $booking,
            'availableDrivers' => $availableDrivers,
        ]);
    }

    /**
     * Approve a pending booking (PIC / Super Admin).
     */
    public function approve(ApproveBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookingService->approveBooking($booking, $request->user());

        return redirect()->back()
            ->with('success', 'Booking request approved successfully.');
    }

    /**
     * Reject a pending booking (PIC / Super Admin).
     */
    public function reject(RejectBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookingService->rejectBooking(
            $booking,
            $request->user(),
            $request->validated('rejection_reason')
        );

        return redirect()->back()
            ->with('success', 'Booking request rejected.');
    }

    /**
     * Assign a driver and vehicle to an approved booking (Super Admin / PIC).
     */
    public function assign(AssignDriverRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookingService->assignDriverAndVehicle(
            $booking,
            $request->validated('driver_id'),
            $request->validated('vehicle_id'),
            $request->user()
        );

        return redirect()->back()
            ->with('success', 'Driver and vehicle successfully assigned to booking.');
    }

    /**
     * Cancel a booking (Employee / Super Admin).
     */
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();

        // Only the requester or Super Admin can cancel
        if ($booking->employee_id !== $user->id && ! $user->isSuperAdmin()) {
            abort(403, 'You are not authorized to cancel this booking.');
        }

        $this->bookingService->cancelBooking($booking, $user);

        return redirect()->back()
            ->with('success', 'Booking has been cancelled.');
    }
}
