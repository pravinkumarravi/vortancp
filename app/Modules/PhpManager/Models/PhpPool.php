<?php

declare(strict_types=1);

namespace App\Modules\PhpManager\Models;

use App\Modules\Accounts\Models\HostingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhpPool extends Model
{
    use HasFactory;

    protected $fillable = [
        'hosting_account_id',
        'version',
        'socket_path',
        'pm_type',
        'pm_max_children',
        'memory_limit',
        'max_execution_time',
        'upload_max_filesize',
        'post_max_size',
        'display_errors',
    ];

    protected $casts = [
        'pm_max_children' => 'integer',
        'max_execution_time' => 'integer',
        'display_errors' => 'boolean',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}
