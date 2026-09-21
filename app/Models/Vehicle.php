<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    // FSM status constants
    public const STATUS_AVAILABLE   = 'available';
    public const STATUS_IN_USE      = 'in_use';
    public const STATUS_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'plate_number',
        'brand',
        'model',
        'color',
        'year',
        'capacity',
        'fuel_type',
        'current_km',
        'current_status',
        'photo',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // ── Query Scopes ───────────────────────────────────────────────────────────

    /**
     * Scope: vehicles NOT double-booked for the given time window.
     *
     * Implements the anti-double-booking check from design.md using
     * pure Eloquent (no raw SQL). Finds vehicles whose status is 'available'
     * AND have no overlapping active booking in the requested time slot.
     */
    public function scopeAvailableFor(Builder $query, string $startTime, string $endTime): Builder
    {
        $blockedStatuses = [
            Booking::STATUS_APPROVED,
            Booking::STATUS_ASSIGNED,
            Booking::STATUS_IN_PROGRESS,
        ];

        return $query
            ->where('current_status', self::STATUS_AVAILABLE)
            ->whereNotIn('id', function ($sub) use ($blockedStatuses, $startTime, $endTime) {
                $sub->select('vehicle_id')
                    ->from('bookings')
                    ->whereIn('status', $blockedStatuses)
                    ->whereNotNull('vehicle_id')
                    ->whereNull('deleted_at')
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('current_status', '!=', self::STATUS_MAINTENANCE);
    }
}
