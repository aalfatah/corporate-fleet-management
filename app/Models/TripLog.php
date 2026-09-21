<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id',
        'start_km',
        'end_km',
        'fuel_receipt_url',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_km' => 'integer',
            'end_km'   => 'integer',
            'total_km' => 'integer', // PostgreSQL stored computed column
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
