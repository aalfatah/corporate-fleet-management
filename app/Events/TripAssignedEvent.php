<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripAssignedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Driver $driver
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bookings'),
            new PrivateChannel('user.' . $this->driver->user_id),
            new PrivateChannel('driver.' . $this->driver->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'TripAssignedEvent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->booking->loadMissing(['vehicle', 'employee.department']);

        return [
            'booking_id'      => $this->booking->id,
            'title'           => 'Tugas Perjalanan Baru!',
            'message'         => 'Anda telah ditugaskan untuk perjalanan dinas ke ' . $this->booking->destination,
            'destination'     => $this->booking->destination,
            'purpose'         => $this->booking->purpose,
            'start_time'      => $this->booking->start_time?->toIso8601String(),
            'end_time'        => $this->booking->end_time?->toIso8601String(),
            'passenger_count' => $this->booking->passenger_count,
            'employee_name'   => $this->booking->employee?->name,
            'department'      => $this->booking->employee?->department?->name,
            'vehicle'         => $this->booking->vehicle ? [
                'id'           => $this->booking->vehicle->id,
                'plate_number' => $this->booking->vehicle->plate_number,
                'brand'        => $this->booking->vehicle->brand,
                'model'        => $this->booking->vehicle->model,
            ] : null,
            'status'          => $this->booking->status,
            'action_url'      => '/bookings/' . $this->booking->id,
            'timestamp'       => now()->toIso8601String(),
        ];
    }
}
