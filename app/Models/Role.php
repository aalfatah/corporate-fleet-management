<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory, HasUuids;

    // Role slug constants used throughout the application
    public const SUPER_ADMIN = 'super_admin';
    public const PIC         = 'pic';
    public const EMPLOYEE    = 'employee';
    public const DRIVER      = 'driver';

    protected $fillable = ['name', 'slug'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
