<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users with role filter and search.
     */
    public function index(Request $request): Response
    {
        $query = User::with(['role', 'department'])
            ->withTrashed(); // Include soft-deleted users

        // Role filter
        if ($roleSlug = $request->input('role')) {
            $query->whereHas('role', fn ($q) => $q->where('slug', $roleSlug));
        }

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        $roles       = Role::orderBy('name')->get(['id', 'name', 'slug']);
        $departments = Department::whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);
        $managers    = User::whereHas('role', fn ($q) => $q->whereIn('slug', [Role::PIC, Role::SUPER_ADMIN]))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Admin/Users/Index', [
            'users'       => $users,
            'roles'       => $roles,
            'departments' => $departments,
            'managers'    => $managers,
            'filters'     => $request->only(['role', 'search']),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['must_change_password'] = $request->boolean('must_change_password', true);

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User account created successfully.');
    }

    /**
     * Update the specified user.
     * Prevents superadmin from changing their own role or deleting themselves.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $authUser = $request->user();
        $data     = $request->validated();

        // Prevent superadmin from changing their own role to avoid lockout
        if ($authUser->id === $user->id && isset($data['role_id']) && $data['role_id'] !== $authUser->role_id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        // Only hash password if it was provided
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User account updated successfully.');
    }

    /**
     * Soft-delete the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Prevent superadmin from deleting themselves
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User account has been deactivated.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(int|string $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('admin.users.index')
            ->with('success', 'User account has been restored.');
    }
}
