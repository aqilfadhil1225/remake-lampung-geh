<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'editor', 'guard_name' => 'web'],
            ['name' => 'reporter', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate($role);
        }
    }
}
