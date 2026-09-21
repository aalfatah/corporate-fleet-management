<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => Role::SUPER_ADMIN],
            ['name' => 'PIC / Approver', 'slug' => Role::PIC],
            ['name' => 'Employee', 'slug' => Role::EMPLOYEE],
            ['name' => 'Driver', 'slug' => Role::DRIVER],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
