<?php

namespace App\Jobs;

use App\Models\ScanRun;
use App\Services\ReconService;
use App\Services\SecretRedactor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunReconJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 1;

    public function __construct(public int $scanRunId)
    {
    }

    public function handle(ReconService $service): void
    {
        $run = ScanRun::findOrFail($this->scanRunId);
        if (in_array($run->status, ['cancelled', 'interrupted'], true)) return;
        $service->run($run);
    }

    public function failed(?Throwable $exception): void
    {
        if ($run = ScanRun::find($this->scanRunId)) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => SecretRedactor::redactString($exception?->getMessage() ?? 'Queue job failed.'),
            ]);
        }
    }
}
