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
        $user = User::create([
            'first_name' => 'Admin', 
            'last_name' => 'Fl', 
            'email' => 'admin@fluidlabs.co.uk',
            'password' => bcrypt('12345678'),
            'email_verified_at'=>now(),
        ]);

        $role = Role::create(['name' => 'Admin']);       
        $permissions = Permission::pluck('id','id')->all();      
        $role->syncPermissions($permissions);       
        $user->assignRole([$role->id]);

    }
}
