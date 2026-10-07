<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('bookings'),
            new PrivateChannel('user.' . $this->booking->employee_id),
        ];

        if ($this->booking->manager_id) {
            $channels[] = new PrivateChannel('user.' . $this->booking->manager_id);
        }

        // Broadcast to assigned driver's user channel
        if ($this->booking->driver_id) {
            $driverUserId = $this->booking->driver?->user_id
                ?? \App\Models\Driver::where('id', $this->booking->driver_id)->value('user_id');

            if ($driverUserId) {
                $channels[] = new PrivateChannel('user.' . $driverUserId);
            }
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'status' => $this->booking->status,
            'vehicle_id' => $this->booking->vehicle_id,
            'driver_id' => $this->booking->driver_id,
            'updated_at' => $this->booking->updated_at?->toIso8601String(),
        ];
    }
}
