<?php

declare(strict_types=1);

namespace App\Modules\Databases\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Databases\Models\Database;
use App\Modules\Databases\Models\DatabaseUser;
use App\Modules\Databases\Models\DatabaseUserAssignment;
use App\Modules\HostingPlans\Services\EntitlementService;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class DatabaseManagerService
{
    public function __construct(
        protected EntitlementService $entitlements,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Provision a new database under an account.
     */
    public function createDatabase(
        HostingAccount $account,
        string $suffix,
        string $engine = 'mysql',
        string $charset = 'utf8mb4',
        string $collation = 'utf8mb4_unicode_ci',
    ): Database {
        $cleanSuffix = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(trim($suffix)));
        if (empty($cleanSuffix)) {
            throw new InvalidArgumentException('Database name suffix must contain alphanumeric characters.');
        }

        $fullName = "{$account->username}_{$cleanSuffix}";

        if (strlen($fullName) > 64) {
            throw new InvalidArgumentException("Database name [{$fullName}] exceeds maximum 64 character length.");
        }

        if (Database::where('name', $fullName)->exists()) {
            throw new InvalidArgumentException("Database [{$fullName}] already exists.");
        }

        // Quota assertion
        $currentCount = Database::where('hosting_account_id', $account->id)->count();
        $this->entitlements->assertCan($account, 'database', $currentCount);

        $db = Database::create([
            'hosting_account_id' => $account->id,
            'server_id' => $account->server_id,
            'name' => $fullName,
            'db_engine' => $engine,
            'charset' => $charset,
            'collation' => $collation,
            'size_bytes' => 0,
            'status' => Database::STATUS_ACTIVE,
        ]);

        $this->auditLogger->log(
            event: 'database.created',
            auditable: $db,
            newValues: ['name' => $fullName, 'engine' => $engine, 'account' => $account->username]
        );

        return $db;
    }

    /**
     * Create a database user.
     */
    public function createDatabaseUser(
        HostingAccount $account,
        string $suffix,
        string $password,
        string $host = 'localhost',
    ): DatabaseUser {
        $cleanSuffix = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(trim($suffix)));
        if (empty($cleanSuffix)) {
            throw new InvalidArgumentException('Database user suffix must contain alphanumeric characters.');
        }

        $fullName = "{$account->username}_{$cleanSuffix}";

        if (strlen($fullName) > 32) {
            throw new InvalidArgumentException("Database username [{$fullName}] exceeds maximum 32 character limit.");
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Database user password must be at least 8 characters.');
        }

        if (DatabaseUser::where('username', $fullName)->exists()) {
            throw new InvalidArgumentException("Database user [{$fullName}] already exists.");
        }

        $user = DatabaseUser::create([
            'hosting_account_id' => $account->id,
            'server_id' => $account->server_id,
            'username' => $fullName,
            'host' => trim($host) ?: 'localhost',
            'password_hash' => Hash::make($password),
            'status' => DatabaseUser::STATUS_ACTIVE,
        ]);

        $this->auditLogger->log(
            event: 'database_user.created',
            auditable: $user,
            newValues: ['username' => $fullName, 'host' => $host, 'account' => $account->username]
        );

        return $user;
    }

    /**
     * Assign user to a database with grants.
     */
    public function assignUserToDatabase(
        Database $database,
        DatabaseUser $user,
        array $privileges = ['ALL'],
    ): DatabaseUserAssignment {
        if ($database->hosting_account_id !== $user->hosting_account_id) {
            throw new InvalidArgumentException('Cannot assign database user to a database from a different hosting account.');
        }

        $assignment = DatabaseUserAssignment::updateOrCreate(
            ['database_id' => $database->id, 'database_user_id' => $user->id],
            ['privileges' => !empty($privileges) ? $privileges : ['ALL']]
        );

        $this->auditLogger->log(
            event: 'database.user_assigned',
            auditable: $database,
            newValues: ['user' => $user->username, 'privileges' => $privileges]
        );

        return $assignment;
    }

    /**
     * Change a database user's password.
     */
    public function changeUserPassword(DatabaseUser $user, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException('New password must be at least 8 characters.');
        }

        $user->update(['password_hash' => Hash::make($newPassword)]);

        $this->auditLogger->log(
            event: 'database_user.password_changed',
            auditable: $user
        );
    }

    /**
     * Delete a database.
     */
    public function deleteDatabase(Database $database): void
    {
        $name = $database->name;
        $this->auditLogger->log(
            event: 'database.deleted',
            auditable: $database,
            oldValues: ['name' => $name]
        );

        $database->delete();
    }

    /**
     * Delete a database user.
     */
    public function deleteDatabaseUser(DatabaseUser $user): void
    {
        $name = $user->username;
        $this->auditLogger->log(
            event: 'database_user.deleted',
            auditable: $user,
            oldValues: ['username' => $name]
        );

        $user->delete();
    }
}
