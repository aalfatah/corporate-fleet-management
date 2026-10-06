<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;
    private User $admin;
    private User $employee;
    private Role $employeeRole;
    private Role $picRole;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'superadmin@fleet.local')->firstOrFail();
        $this->employee = User::where('email', 'budi.santoso@fleet.local')->firstOrFail();
        $this->employeeRole = Role::where('slug', Role::EMPLOYEE)->firstOrFail();
        $this->picRole = Role::where('slug', Role::PIC)->firstOrFail();
        $this->department = Department::firstOrFail();
    }

    public function test_superadmin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');
        $response->assertStatus(200);
    }

    public function test_non_superadmin_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->employee)->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_superadmin_can_create_new_user_and_pic(): void
    {
        $email = 'new.pic.test_' . time() . '@fleet.local';

        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name'                 => 'New PIC Account',
            'email'                => $email,
            'password'             => 'P@ssw0rd123!',
            'role_id'              => $this->picRole->id,
            'department_id'        => $this->department->id,
            'phone'                => '+62 812 3456 7890',
            'must_change_password' => true,
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');

        $created = User::where('email', $email)->first();
        $this->assertNotNull($created);
        $this->assertEquals('New PIC Account', $created->name);
        $this->assertTrue($created->must_change_password);
        $this->assertTrue(Hash::check('P@ssw0rd123!', $created->password));
    }

    public function test_superadmin_can_update_user(): void
    {
        $email = 'to_update_' . time() . '@fleet.local';
        $user = User::create([
            'name'                 => 'Temporary User',
            'email'                => $email,
            'password'             => Hash::make('Secret123!'),
            'role_id'              => $this->employeeRole->id,
            'department_id'        => $this->department->id,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name'                 => 'Updated User Name',
            'email'                => $email,
            'role_id'              => $this->picRole->id,
            'department_id'        => $this->department->id,
            'phone'                => '+62 899 9999 9999',
            'must_change_password' => true,
        ]);

        $response->assertRedirect('/admin/users');
        $user->refresh();
        $this->assertEquals('Updated User Name', $user->name);
        $this->assertEquals($this->picRole->id, $user->role_id);
    }

    public function test_superadmin_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->id}");
        $response->assertSessionHas('error');
        $this->assertNull($this->admin->fresh()->deleted_at);
    }

    public function test_superadmin_can_soft_delete_and_restore_user(): void
    {
        $email = 'deactivate_test_' . time() . '@fleet.local';
        $user = User::create([
            'name'                 => 'User To Delete',
            'email'                => $email,
            'password'             => Hash::make('Secret123!'),
            'role_id'              => $this->employeeRole->id,
            'department_id'        => $this->department->id,
            'must_change_password' => false,
        ]);

        // Soft delete
        $deleteResponse = $this->actingAs($this->admin)->delete("/admin/users/{$user->id}");
        $deleteResponse->assertRedirect('/admin/users');
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // Restore
        $restoreResponse = $this->actingAs($this->admin)->post("/admin/users/{$user->id}/restore");
        $restoreResponse->assertRedirect('/admin/users');
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }
}
