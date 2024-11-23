<?php

namespace Tests\Unit\app\Listeners;

use App\Listeners\JobEventListener;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JobEventListenerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_handles_job_processing_event(): void // phpcs:ignore
    {
        $event = Mockery::mock(JobProcessing::class);
        $event->job = Mockery::mock(); // @phpstan-ignore-line
        $event->job->shouldReceive('resolveName')->andReturn('ExampleJob');

        Log::shouldReceive('info')
            ->once()
            ->with('Job is starting: ExampleJob');

        $listener = new JobEventListener();
        $listener->handle($event);
    }

    #[Test]
    public function it_handles_job_processed_event(): void // phpcs:ignore
    {
        $event = Mockery::mock(JobProcessed::class);
        $event->job = Mockery::mock(); // @phpstan-ignore-line
        $event->job->shouldReceive('resolveName')->andReturn('ExampleJob');

        Log::shouldReceive('info')
            ->once()
            ->with('Job processed successfully: ExampleJob');

        $listener = new JobEventListener();
        $listener->handle($event);
    }

    #[Test]
    public function it_handles_job_failed_event(): void // phpcs:ignore
    {
        $event = Mockery::mock(JobFailed::class);
        $event->job = Mockery::mock(); // @phpstan-ignore-line
        $event->job->shouldReceive('resolveName')->andReturn('ExampleJob');

        Log::shouldReceive('error')
            ->once()
            ->with('Job failed: ExampleJob');

        $listener = new JobEventListener();
        $listener->handle($event);
    }

    #[Test]
    public function it_handles_job_exception_occurred_event(): void // phpcs:ignore
    {
        $exceptionMessage = 'Test exception message';
        $exception = new Exception($exceptionMessage);

        $event = Mockery::mock(JobExceptionOccurred::class);
        $event->job = Mockery::mock(); // @phpstan-ignore-line
        $event->exception = $exception; // @phpstan-ignore-line
        $event->job->shouldReceive('resolveName')->andReturn('ExampleJob');

        Log::shouldReceive('warning')
            ->once()
            ->with("Exception occurred in job: ExampleJob - $exceptionMessage");

        $listener = new JobEventListener();
        $listener->handle($event);
    }
}
