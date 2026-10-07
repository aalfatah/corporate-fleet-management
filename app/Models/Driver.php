<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'license_number',
        'license_expiry',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'is_active'      => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** All bookings this driver has been assigned to */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // ── Query Scopes ───────────────────────────────────────────────────────────

    /**
     * Scope: drivers NOT currently occupied during the requested time window.
     *
     * A driver is considered occupied if they are in an 'assigned' or
     * 'in_progress' booking that overlaps with the requested slot.
     */
    public function scopeAvailableFor(Builder $query, string $startTime, string $endTime, ?string $excludeBookingId = null): Builder
    {
        $blockedStatuses = [
            Booking::STATUS_ASSIGNED,
            Booking::STATUS_IN_PROGRESS,
        ];

        return $query
            ->where('is_active', true)
            ->whereNotIn('id', function ($sub) use ($blockedStatuses, $startTime, $endTime, $excludeBookingId) {
                $sub->select('driver_id')
                    ->from('bookings')
                    ->whereIn('status', $blockedStatuses)
                    ->whereNotNull('driver_id')
                    ->whereNull('deleted_at')
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);

                if ($excludeBookingId) {
                    $sub->where('id', '!=', $excludeBookingId);
                }
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
