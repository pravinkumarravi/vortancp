<?php

declare(strict_types=1);

namespace App\Modules\Migration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationJob extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ANALYZING = 'analyzing';
    public const STATUS_MIGRATING = 'migrating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'source_type',
        'archive_name',
        'archive_path',
        'archive_size_bytes',
        'target_username',
        'target_domain',
        'status',
        'step_progress',
        'current_step',
        'log_messages',
        'migrated_assets',
        'error_message',
    ];

    protected $casts = [
        'archive_size_bytes' => 'integer',
        'step_progress' => 'integer',
        'log_messages' => 'array',
        'migrated_assets' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addLog(string $message): self
    {
        $logs = $this->log_messages ?? [];
        $logs[] = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'message' => $message,
        ];
        $this->log_messages = $logs;
        $this->save();

        return $this;
    }

    public function updateProgress(int $percent, string $step): self
    {
        $this->step_progress = min(100, max(0, $percent));
        $this->current_step = $step;
        $this->addLog("[{$percent}%] {$step}");
        $this->save();

        return $this;
    }

    public function markCompleted(array $assets = []): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->step_progress = 100;
        $this->current_step = 'Migration completed successfully';
        $this->migrated_assets = $assets;
        $this->addLog('[100%] Migration completed successfully.');
        $this->save();

        return $this;
    }

    public function markFailed(string $error): self
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $error;
        $this->current_step = 'Migration failed';
        $this->addLog("[ERROR] {$error}");
        $this->save();

        return $this;
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->archive_size_bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }
}
