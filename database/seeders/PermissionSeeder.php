<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all VMS permissions
        $permissions = [
            'view visitors',
            'create visit',
            'verify visit otp',
            'approve visit',
            'reject visit',
            'checkin visit',
            'checkout visit',
            'view live dashboard',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Get or create admin role
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        // Give admin role all permissions
        $adminRole->givePermissionTo($permissions);

        // Get or create receptionist role
        $receptionistRole = Role::firstOrCreate([
            'name' => 'receptionist',
            'guard_name' => 'web'
        ]);

        // Give receptionist role specific permissions
        $receptionistPermissions = [
            'view visitors',
            'create visit',
            'verify visit otp',
            'checkin visit',
            'checkout visit',
            'view live dashboard',
        ];
        $receptionistRole->givePermissionTo($receptionistPermissions);

        // Get or create staff role
        $staffRole = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web'
        ]);

        // Give staff role specific permissions (for host approval and check-in/out)
        $staffPermissions = [
            'view visitors',
            'approve visit',
            'reject visit',
            'checkin visit',
            'checkout visit',
            'view live dashboard',
        ];
        $staffRole->givePermissionTo($staffPermissions);

        // Get or create visitor role
        $visitorRole = Role::firstOrCreate([
            'name' => 'visitor',
            'guard_name' => 'web'
        ]);

        // Give visitor role minimal permissions (for viewing their own visits)
        $visitorPermissions = [
            'view visitors',
        ];
        $visitorRole->givePermissionTo($visitorPermissions);

        $this->command->info('VMS permissions seeded successfully!');
        $this->command->info('Permissions created: ' . implode(', ', $permissions));
        $this->command->newLine();
        $this->command->info('Role assignments:');
        $this->command->info('- Admin: All permissions');
        $this->command->info('- Receptionist: View, Create, Verify OTP, Check-in/out, Live Dashboard');
        $this->command->info('- Staff: View, Approve/Reject, Live Dashboard');
        $this->command->info('- Visitor: View only');
    }
}
