<?php

namespace App\Providers;

use App\Repository\Eloquent\v1\Basic\UserRepository;
use App\Repository\Eloquent\v1\Jira\JiraIssueRepository;
use App\Repository\Eloquent\v1\Jira\JiraProjectRepository;
use App\Repository\Eloquent\v1\Jira\JiraTeamRepository;
use App\Repository\Eloquent\v1\Jira\JiraUserRepository;
use App\Repository\Eloquent\v1\Tempo\TempoUserRepository;
use App\Repository\Eloquent\v1\Tempo\TimeEntryRepository;
use App\Repository\Interfaces\v1\Basic\UserRepositoryInterface;
use App\Repository\Interfaces\v1\Jira\JiraIssueRepositoryInterface;
use App\Repository\Interfaces\v1\Jira\JiraProjectRepositoryInterface;
use App\Repository\Interfaces\v1\Jira\JiraTeamRepositoryInterface;
use App\Repository\Interfaces\v1\Jira\JiraUserRepositoryInterface;
use App\Repository\Interfaces\v1\Tempo\TempoUserRepositoryInterface;
use App\Repository\Interfaces\v1\Tempo\TimeEntryRepositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Class RepositoryServiceProvider
 *
 * @package   App\Providers
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserRepositoryInterface::class, UserRepository::class);
        $this->app->singleton(JiraUserRepositoryInterface::class, JiraUserRepository::class);
        $this->app->singleton(JiraTeamRepositoryInterface::class, JiraTeamRepository::class);
        $this->app->singleton(JiraProjectRepositoryInterface::class, JiraProjectRepository::class);
        $this->app->singleton(JiraIssueRepositoryInterface::class, JiraIssueRepository::class);
        $this->app->singleton(TempoUserRepositoryInterface::class, TempoUserRepository::class);
        $this->app->singleton(TimeEntryRepositoryInterface::class, TimeEntryRepository::class);
    }
}
