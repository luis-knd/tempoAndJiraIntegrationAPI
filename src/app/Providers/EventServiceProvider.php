<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobExceptionOccurred;
use App\Listeners\JobEventListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JobProcessing::class => [
            JobEventListener::class,
        ],
        JobProcessed::class => [
            JobEventListener::class,
        ],
        JobExceptionOccurred::class => [
            JobEventListener::class,
        ],
        JobFailed::class => [
            JobEventListener::class,
        ],
    ];
}
