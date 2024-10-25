<?php
namespace Database\Seeders;

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
        // Create or find the admin user
        $user = User::firstOrCreate(
            ['email' => 'wp@fluidlabs.co.uk'],
            [
                'first_name' => 'Admin',
                'last_name' => 'FluidlabsUK',
                'password' => bcrypt('123456789012'),
                'email_verified_at' => now(),
            ]
        );

        // Create or find the Admin role
        $role = Role::firstOrCreate(['name' => 'Admin','guard_name' => 'api']);        

        // Assign all permissions to the Admin role
        $role->syncPermissions(Permission::all());

        // Assign the Admin role to the user
        $user->assignRole($role);
    }
}
