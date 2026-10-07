<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments (active + soft-deleted) with search.
     */
    public function index(Request $request): Response
    {
        $query = Department::withTrashed()->withCount('users');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        $showInactive = $request->boolean('show_inactive', false);
        if (! $showInactive) {
            $query->whereNull('deleted_at');
        }

        $departments = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Departments/Index', [
            'departments'   => $departments,
            'filters'       => $request->only(['search', 'show_inactive']),
        ]);
    }

    /**
     * Store a newly created department.
     */
    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Update the specified department.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Soft-delete the specified department.
     * Refuses if there are active users still assigned to it.
     */
    public function destroy(Department $department): RedirectResponse
    {
        $activeUsers = $department->users()->whereNull('deleted_at')->count();

        if ($activeUsers > 0) {
            return back()->with(
                'error',
                "Cannot deactivate \"{$department->name}\" — {$activeUsers} active user(s) are still assigned to it. Reassign them first."
            );
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deactivated.');
    }

    /**
     * Restore a soft-deleted department.
     */
    public function restore(string $id): RedirectResponse
    {
        $department = Department::withTrashed()->findOrFail($id);
        $department->restore();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department restored.');
    }
}
