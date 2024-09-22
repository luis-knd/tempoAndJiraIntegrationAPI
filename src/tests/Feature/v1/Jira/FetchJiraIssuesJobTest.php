<?php

namespace Tests\Feature\v1\Jira;

use App\Jobs\v1\Jira\FetchJiraIssuesJob;
use App\Jobs\v1\Jira\FetchJiraProjectsJob;
use App\Jobs\v1\Jira\FetchJiraUsersJob;
use App\Services\v1\Jira\JiraApiService;
use App\Services\v1\Tempo\TempoApiService;
use Database\Seeders\v1\Jira\JiraProjectCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchJiraIssuesJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_should_fetch_and_store_jira_issues_from_real_api(): void // phpcs:ignore
    {
        self::markTestSkipped("If you want to test the real API, run this test. And adapt in base for your real data");
        $jiraApiService = resolve(JiraApiService::class);
        $this->seed(JiraProjectCategoriesSeeder::class);
        $jiraProjectsJob = new FetchJiraProjectsJob();
        $jiraProjectsJob->handle($jiraApiService);
        $jiraUsersJob = new FetchJiraUsersJob('atlassian');
        $jiraUsersJob->handle($jiraApiService);
        $syncId = Str::uuid()->toString();
        $jiraIssuesJob = new FetchJiraIssuesJob('filter = "Issues 2.0"', $syncId);

        $jiraIssuesJob->handle($jiraApiService);

        $this->assertDatabaseHas('jira_issues', [
            'jira_issue_id' => 172493,
            'jira_issue_key' => 'VF2-89',
            'jira_project_id' => 11763,
            'summary' => '[MS] - Integración de los servicios Auditoría con Firma Blockchain',
            'status' => 'Nuevo',
            'development_category' => 'Sin categoría asignada'
        ]);
        $this->assertDatabaseHas('time_entries', [
           'jira_user_id' => '557058:fb745e64-a430-4aa7-8bcc-5a240d85b65b',
           'time_spent_in_minutes' => 15.0

        ]);
    }
}
