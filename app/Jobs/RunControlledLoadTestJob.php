<?php

namespace App\Jobs;

use App\Models\LoadTest;
use App\Services\ControlledLoadTestService;
use App\Services\SecretRedactor;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunControlledLoadTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $loadTestId)
    {
    }

    public function handle(ControlledLoadTestService $service): void
    {
        $loadTest = LoadTest::findOrFail($this->loadTestId);
        if (! $loadTest->explicitly_confirmed) return;
        if (! $loadTest->scanRun || in_array($loadTest->scanRun->status, ['cancelled', 'interrupted'], true)) return;
        $service->run($loadTest);
    }

    public function failed(?Throwable $exception): void
    {
        $loadTest = LoadTest::with('scanRun')->find($this->loadTestId);
        if ($loadTest?->scanRun) {
            $loadTest->scanRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => SecretRedactor::redactString($exception?->getMessage() ?? 'Queue job failed.'),
            ]);
        }
    }
}
