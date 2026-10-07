<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('slug', Role::SUPER_ADMIN)->firstOrFail();
        $itDept = Department::where('code', 'IT')->firstOrFail();

        User::firstOrCreate(
            ['email' => 'ahmat.alfatah@homecc.com'],
            [
                'name' => 'Ahmat Alfatah',
                'password' => Hash::make('Admin@1234!'),
                'role_id' => $superAdminRole->id,
                'department_id' => $itDept->id,
                'manager_id' => null,
            ]
        );
    }
}
