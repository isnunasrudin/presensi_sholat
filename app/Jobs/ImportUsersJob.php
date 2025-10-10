<?php

namespace App\Jobs;

use App\Imports\UsersImportOptimized;
use App\Models\UserImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ImportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry delays

    protected $userImport;
    protected $filePath;
    protected $adminUserId;

    /**
     * Create a new job instance.
     */
    public function __construct(UserImport $userImport, string $filePath, int $adminUserId)
    {
        $this->userImport = $userImport;
        $this->filePath = $filePath;
        $this->adminUserId = $adminUserId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to processing
            $this->userImport->update([
                'status' => 'processing',
                'started_at' => now(),
                'progress' => 0,
            ]);

            Log::info("Starting user import job: {$this->userImport->id}");

            // Create import instance with progress tracking
            $import = new class extends UsersImportOptimized {
                protected $userImport;

                public function __construct($userImport)
                {
                    parent::__construct();
                    $this->userImport = $userImport;
                }

                public function chunk(array $rows): void
                {
                    parent::chunk($rows);

                    // Update progress
                    $totalProcessed = $this->importedCount + $this->skippedCount;
                    $progress = min(round(($totalProcessed / max($this->userImport->total_rows, 1)) * 100, 2), 100);

                    $this->userImport->update([
                        'progress' => $progress,
                        'imported_count' => $this->importedCount,
                        'skipped_count' => $this->skippedCount,
                        'errors' => $this->errors,
                    ]);

                    // Log progress
                    Log::info("Import progress: {$progress}% ({$this->importedCount} imported, {$this->skippedCount} skipped)");
                }
            };

            // Get file path
            $fullPath = Storage::disk('local')->path($this->filePath);

            // Execute import
            Excel::import($import, $fullPath);

            // Get results
            $results = $import->getResults();

            // Update final status
            $this->userImport->update([
                'status' => 'completed',
                'completed_at' => now(),
                'progress' => 100,
                'imported_count' => $results['imported_count'],
                'skipped_count' => $results['skipped_count'],
                'errors' => $results['errors'],
                'execution_time' => $results['execution_time'],
                'memory_usage' => $results['memory_usage'],
                'performance' => $results['performance'],
            ]);

            Log::info("User import completed: {$results['imported_count']} imported in {$results['execution_time']}s");

            // Clean up file
            Storage::disk('local')->delete($this->filePath);

        } catch (\Exception $e) {
            Log::error("User import failed: " . $e->getMessage());

            // Update status to failed
            $this->userImport->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            // Re-queue job if retries available
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("User import job failed permanently: " . $exception->getMessage());

        $this->userImport->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}