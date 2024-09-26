<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Fl',
            'email' => 'admin@fluidlabs.co.uk',
            'password' => bcrypt('12345678'),
            'email_verified_at' => now(),
        ]);

        // Create roles and permissions
        $role = Role::create(['name' => 'Admin']);

        // Define permissions
        $permissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'project-list',
            'project-create',
            'project-edit',
            'project-delete',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to the Admin role
        $role->syncPermissions(Permission::all());

        // Assign the role to the admin user
        $user->assignRole($role);
    }
}
