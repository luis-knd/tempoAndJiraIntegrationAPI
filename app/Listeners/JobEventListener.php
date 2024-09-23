<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Support\Facades\Log;

class JobEventListener
{
    public function handle($event): void
    {
        if ($event instanceof JobProcessing) {
            $this->onJobProcessing($event);
        } elseif ($event instanceof JobProcessed) {
            $this->onJobProcessed($event);
        } elseif ($event instanceof JobExceptionOccurred) {
            $this->onJobExceptionOccurred($event);
        } elseif ($event instanceof JobFailed) {
            $this->onJobFailed($event);
        }
    }

    public function onJobProcessing(JobProcessing $event): void
    {
        Log::info('Job is starting: ' . $event->job->resolveName());
    }

    public function onJobProcessed(JobProcessed $event): void
    {
        Log::info('Job processed successfully: ' . $event->job->resolveName());
    }

    public function onJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        Log::warning(sprintf(
            "Exception occurred in job: %s - %s",
            $event->job->resolveName(),
            $event->exception->getMessage()
        ));
    }

    public function onJobFailed(JobFailed $event): void
    {
        Log::error('Job failed: ' . $event->job->resolveName());
    }
}
