<?php

namespace App\Providers;

use App\Services\Interfaces\Jira\JiraApiServiceInterface;
use App\Services\Interfaces\Tempo\TempoApiServiceInterface;
use App\Services\v1\Jira\JiraApiService;
use App\Services\v1\Tempo\TempoApiService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JiraApiServiceInterface::class, JiraApiService::class);
        $this->app->singleton(TempoApiServiceInterface::class, TempoApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(static function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
            );
        });
    }
}
