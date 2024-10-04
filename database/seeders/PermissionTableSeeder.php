<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'project-list',
            'project-create',
            'project-edit',
            'project-delete',
            'task-list',
            'task-create',
            'task-edit',
            'task-delete',
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'subscriptions_package-list',
            'subscriptions_package-create',
            'subscriptions_package-edit',
            'subscriptions_package-delete',
            'customer-list',
            'customer-create',
            'customer-edit',
            'customer-delete',
        ];
        
        foreach ($permissions as $permission) {
            // Permission::create(['name' => $permission]);
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }
    }
}
