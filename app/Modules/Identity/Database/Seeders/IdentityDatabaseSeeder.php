<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Seeders;

use App\Models\User;
use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class IdentityDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Core Roles
        $roles = [
            Role::SUPER_ADMIN => [
                'label' => 'Super Administrator',
                'description' => 'Full unrestricted control across all servers, accounts, resellers, and global configurations.',
            ],
            Role::SERVER_ADMIN => [
                'label' => 'Server Administrator',
                'description' => 'Infrastructure management, service control, and system health oversight.',
            ],
            Role::RESELLER => [
                'label' => 'Reseller',
                'description' => 'Manages assigned customer accounts and hosting packages within allocated resource pools.',
            ],
            Role::CUSTOMER => [
                'label' => 'Hosting Customer',
                'description' => 'Customer account managing websites, domains, DNS, files, databases, and mailboxes.',
            ],
            Role::TEAM_MEMBER => [
                'label' => 'Team Member',
                'description' => 'Delegated access with specific site or database permissions.',
            ],
            Role::READ_ONLY => [
                'label' => 'Read-Only Auditor',
                'description' => 'Audit and inspection capabilities without modification permissions.',
            ],
        ];

        $createdRoles = [];
        foreach ($roles as $name => $data) {
            $createdRoles[$name] = Role::firstOrCreate(['name' => $name], $data);
        }

        // 2. Create Base Permissions
        $permissions = [
            'servers.view' => ['label' => 'View Servers', 'module' => 'servers'],
            'servers.manage' => ['label' => 'Manage Servers', 'module' => 'servers'],
            'services.manage' => ['label' => 'Control System Services', 'module' => 'servers'],
            'users.view' => ['label' => 'View Users', 'module' => 'identity'],
            'users.manage' => ['label' => 'Manage Users', 'module' => 'identity'],
            'roles.manage' => ['label' => 'Manage Roles and Permissions', 'module' => 'identity'],
            'resellers.view' => ['label' => 'View Resellers', 'module' => 'resellers'],
            'resellers.manage' => ['label' => 'Manage Resellers', 'module' => 'resellers'],
            'accounts.view' => ['label' => 'View Hosting Accounts', 'module' => 'accounts'],
            'accounts.manage' => ['label' => 'Manage Hosting Accounts', 'module' => 'accounts'],
            'domains.view' => ['label' => 'View Domains', 'module' => 'domains'],
            'domains.create' => ['label' => 'Create Domains', 'module' => 'domains'],
            'domains.delete' => ['label' => 'Delete Domains', 'module' => 'domains'],
            'dns.manage' => ['label' => 'Manage DNS Zones', 'module' => 'dns'],
            'ssl.manage' => ['label' => 'Manage SSL Certificates', 'module' => 'ssl'],
            'files.manage' => ['label' => 'Manage Files', 'module' => 'filemanager'],
            'databases.manage' => ['label' => 'Manage Databases', 'module' => 'databases'],
            'email.manage' => ['label' => 'Manage Mailboxes', 'module' => 'email'],
            'backups.manage' => ['label' => 'Manage Backups', 'module' => 'backups'],
            'audit.view' => ['label' => 'View Audit Logs', 'module' => 'audit'],
        ];

        foreach ($permissions as $name => $data) {
            Permission::firstOrCreate(['name' => $name], $data);
        }

        // 3. Assign Permissions to Server Admin
        $serverAdminPerms = Permission::whereIn('name', [
            'servers.view', 'servers.manage', 'services.manage',
            'accounts.view', 'accounts.manage',
            'domains.view', 'dns.manage', 'ssl.manage',
            'audit.view'
        ])->get();
        $createdRoles[Role::SERVER_ADMIN]->permissions()->syncWithoutDetaching($serverAdminPerms);

        // 4. Assign Permissions to Reseller
        $resellerPerms = Permission::whereIn('name', [
            'accounts.view', 'accounts.manage',
            'domains.view', 'domains.create', 'domains.delete',
            'dns.manage', 'ssl.manage', 'files.manage', 'databases.manage', 'email.manage'
        ])->get();
        $createdRoles[Role::RESELLER]->permissions()->syncWithoutDetaching($resellerPerms);

        // 5. Assign Permissions to Customer
        $customerPerms = Permission::whereIn('name', [
            'domains.view', 'domains.create', 'domains.delete',
            'dns.manage', 'ssl.manage', 'files.manage', 'databases.manage', 'email.manage', 'backups.manage'
        ])->get();
        $createdRoles[Role::CUSTOMER]->permissions()->syncWithoutDetaching($customerPerms);

        // 6. Create Default Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@vortan.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('VortanAdmin#2026!'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole(Role::SUPER_ADMIN);

        // 7. Create Demo Reseller
        $resellerUser = User::firstOrCreate(
            ['email' => 'reseller@vortan.local'],
            [
                'name' => 'Apex Reseller',
                'password' => Hash::make('VortanReseller#2026!'),
                'email_verified_at' => now(),
            ]
        );
        $resellerUser->assignRole(Role::RESELLER);

        // 8. Create Demo Customer
        $customerUser = User::firstOrCreate(
            ['email' => 'customer@vortan.local'],
            [
                'name' => 'Acme Web Client',
                'password' => Hash::make('VortanCustomer#2026!'),
                'email_verified_at' => now(),
            ]
        );
        $customerUser->assignRole(Role::CUSTOMER);
    }
}
