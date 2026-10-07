<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    // ── FSM Status Constants ───────────────────────────────────────────────────
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED         = 'approved';
    public const STATUS_ASSIGNED         = 'assigned';
    public const STATUS_IN_PROGRESS      = 'in_progress';
    public const STATUS_COMPLETED        = 'completed';
    public const STATUS_REJECTED         = 'rejected';
    public const STATUS_CANCELLED        = 'cancelled';

    /**
     * Valid FSM state transitions map.
     *
     * Key   = current status
     * Value = array of allowed next statuses
     *
     * This map is the single source of truth for all transitions.
     * It is consumed by BookingService::assertCanTransition() AFTER
     * a lockForUpdate() has been acquired — making the check atomic.
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING_APPROVAL => [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_APPROVED  => [self::STATUS_ASSIGNED, self::STATUS_CANCELLED],
        self::STATUS_ASSIGNED  => [
            self::STATUS_IN_PROGRESS,
            self::STATUS_ASSIGNED, // Reassign driver or car before trip begins
            self::STATUS_CANCELLED,
        ],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED => [],
        self::STATUS_REJECTED  => [],
        self::STATUS_CANCELLED => [],
    ];

    protected $fillable = [
        'employee_id',
        'manager_id',
        'vehicle_id',
        'driver_id',
        'start_time',
        'end_time',
        'destination',
        'purpose',
        'passenger_count',
        'rejection_reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time'   => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function tripLog(): HasOne
    {
        return $this->hasOne(TripLog::class);
    }

    // ── FSM Helper Methods ─────────────────────────────────────────────────────

    /** Check if transitioning to $newStatus is a valid FSM move from current status. */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isPending(): bool    { return $this->status === self::STATUS_PENDING_APPROVAL; }
    public function isApproved(): bool   { return $this->status === self::STATUS_APPROVED; }
    public function isAssigned(): bool   { return $this->status === self::STATUS_ASSIGNED; }
    public function isInProgress(): bool { return $this->status === self::STATUS_IN_PROGRESS; }
    public function isCompleted(): bool  { return $this->status === self::STATUS_COMPLETED; }
    public function isRejected(): bool   { return $this->status === self::STATUS_REJECTED; }
    public function isCancelled(): bool  { return $this->status === self::STATUS_CANCELLED; }

    // ── Query Scopes ───────────────────────────────────────────────────────────

    public function scopeForEmployee(Builder $query, string $userId): Builder
    {
        return $query->where('employee_id', $userId);
    }

    public function scopeForManager(Builder $query, string $userId): Builder
    {
        return $query->where('manager_id', $userId);
    }

    public function scopeForDriver(Builder $query, string $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    /** Active = any booking that currently occupies a vehicle or driver. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
        ]);
    }
}
