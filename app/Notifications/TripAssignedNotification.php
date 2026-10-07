<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TripAssignedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public Driver $driver,
        public ?User $assignedBy = null
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing(['vehicle', 'employee.department']);

        return [
            'booking_id'      => $this->booking->id,
            'type'            => 'trip_assigned',
            'title'           => 'Tugas Perjalanan Baru!',
            'message'         => 'Anda telah ditugaskan untuk perjalanan dinas ke ' . $this->booking->destination . '.',
            'destination'     => $this->booking->destination,
            'purpose'         => $this->booking->purpose,
            'start_time'      => $this->booking->start_time?->toIso8601String(),
            'end_time'        => $this->booking->end_time?->toIso8601String(),
            'passenger_count' => $this->booking->passenger_count,
            'employee_name'   => $this->booking->employee?->name,
            'department'      => $this->booking->employee?->department?->name,
            'vehicle'         => $this->booking->vehicle ? [
                'plate_number' => $this->booking->vehicle->plate_number,
                'brand'        => $this->booking->vehicle->brand,
                'model'        => $this->booking->vehicle->model,
            ] : null,
            'assigned_by'     => $this->assignedBy?->name ?? 'PIC / Admin',
            'action_url'      => '/bookings/' . $this->booking->id,
            'created_at'      => now()->toIso8601String(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
