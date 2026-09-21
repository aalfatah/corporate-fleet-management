<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role_id',
        'department_id',
        'manager_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** The user's direct manager */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** Users who report to this user */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /** If this user is also a registered driver profile */
    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    /** Bookings made BY this user as an employee */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'employee_id');
    }

    /** Bookings this user must approve as manager/PIC */
    public function bookingsToApprove(): HasMany
    {
        return $this->hasMany(Booking::class, 'manager_id');
    }

    // ── Role Helper Methods ────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role->slug === Role::SUPER_ADMIN;
    }

    public function isPic(): bool
    {
        return $this->role->slug === Role::PIC;
    }

    public function isEmployee(): bool
    {
        return $this->role->slug === Role::EMPLOYEE;
    }

    public function isDriver(): bool
    {
        return $this->role->slug === Role::DRIVER;
    }
}
