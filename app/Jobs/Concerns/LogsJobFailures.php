<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Log;
use Throwable;

trait LogsJobFailures
{
    public function failed(Throwable $exception): void
    {
        Log::error('Queued job failed.', [
            'job' => static::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
