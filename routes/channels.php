<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('bookings', function (User $user) {
    return true; // All authenticated users can listen to fleet events
});

Broadcast::channel('user.{id}', function (User $user, string $id) {
    return $user->id === $id || $user->isSuperAdmin();
});

Broadcast::channel('driver.{id}', function (User $user, string $id) {
    return $user->driver?->id === $id || $user->isSuperAdmin();
});
