<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the profile page for the currently authenticated user.
     */
    public function show(): Response
    {
        return Inertia::render('Profile/Index', [
            'user' => auth()->user()->load(['role', 'department']),
        ]);
    }

    /**
     * Update the user's profile information (name, phone, avatar).
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     * Uses current_password validation rule (built-in Laravel) to verify old password.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $wasForced = (bool) $user->must_change_password;

        $user->update([
            'password'             => $request->validated('password'),
            'must_change_password' => false, // Clear the force-change flag after user sets own password
        ]);

        if ($wasForced) {
            return redirect()->route('dashboard')
                ->with('success', 'Password updated successfully. You now have full access to the system.');
        }

        return back()->with('success', 'Password changed successfully. Please keep it secure.');
    }
}
