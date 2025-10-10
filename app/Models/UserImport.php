<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_user_id',
        'file_name',
        'file_path',
        'total_rows',
        'imported_count',
        'skipped_count',
        'errors',
        'status',
        'progress',
        'started_at',
        'completed_at',
        'failed_at',
        'execution_time',
        'memory_usage',
        'performance',
        'error_message',
    ];

    protected $casts = [
        'errors' => 'array',
        'performance' => 'array',
        'progress' => 'decimal:2',
        'execution_time' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the admin who initiated the import.
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Check if import is completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if import is currently processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->imported_count / $this->total_rows) * 100, 2);
    }

    /**
     * Get formatted execution time.
     */
    public function getFormattedExecutionTimeAttribute(): string
    {
        if (!$this->execution_time) {
            return 'N/A';
        }

        if ($this->execution_time < 60) {
            return round($this->execution_time, 2) . ' detik';
        }

        return round($this->execution_time / 60, 2) . ' menit';
    }

    /**
     * Get total processed count.
     */
    public function getTotalProcessedAttribute(): int
    {
        return $this->imported_count + $this->skipped_count;
    }
}