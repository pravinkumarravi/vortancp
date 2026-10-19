<?php

declare(strict_types=1);

namespace App\Modules\Databases\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseUserAssignment extends Model
{
    use HasFactory;

    public const ALL_PRIVILEGES = [
        'ALL',
        'SELECT',
        'INSERT',
        'UPDATE',
        'DELETE',
        'CREATE',
        'DROP',
        'INDEX',
        'ALTER',
        'CREATE TEMPORARY TABLES',
        'LOCK TABLES',
        'EXECUTE',
        'CREATE VIEW',
        'SHOW VIEW',
        'CREATE ROUTINE',
        'ALTER ROUTINE',
        'EVENT',
        'TRIGGER',
    ];

    protected $fillable = [
        'database_id',
        'database_user_id',
        'privileges',
    ];

    protected $casts = [
        'privileges' => 'array',
    ];

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function databaseUser(): BelongsTo
    {
        return $this->belongsTo(DatabaseUser::class);
    }
}
