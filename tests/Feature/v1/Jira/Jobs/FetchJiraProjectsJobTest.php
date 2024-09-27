<?php

namespace Feature\v1\Jira\Jobs;

use App\Jobs\v1\Jira\FetchJiraProjectsJob;
use App\Models\v1\Jira\JiraProjectCategory;
use App\Services\v1\Jira\JiraApiService;
use Database\Seeders\v1\Jira\JiraProjectCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchJiraProjectsJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_should_fetch_and_store_jira_projects_from_real_api(): void // phpcs:ignore
    {
        self::markTestSkipped("If you want to test the real API, run this test. And adapt in base for your real data");
        $jiraApiService = resolve(JiraApiService::class);
        $job = new FetchJiraProjectsJob();
        $this->seed(JiraProjectCategoriesSeeder::class);

        $job->handle($jiraApiService);

        $this->assertDatabaseCount('jira_projects', 56);
        $this->assertDatabaseHas('jira_projects', [
            'jira_project_id' => 11100,
            'jira_project_key' => 'AUD',
            'name' => 'Auditor',
            'jira_project_category_id' => 10001
        ]);
    }

    #[Test]
    public function it_should_fetch_and_store_jira_projects_from_mock_api(): void  // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        JiraProjectCategory::factory()->count(1)->create(['jira_category_id' => 20001]);

        $jiraApiServiceMock->method('fetchProjects')->willReturn([
            'values' => [
                [
                    'id' => 11100,
                    'key' => 'AUD',
                    'name' => 'Auditor',
                    'projectCategory' => [
                        'id' => 20001,
                    ],
                ],
            ],
        ]);
        $job = new FetchJiraProjectsJob();

        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseCount('jira_projects', 1);
        $this->assertDatabaseHas('jira_projects', [
            'jira_project_id' => 11100,
            'jira_project_key' => 'AUD',
            'name' => 'Auditor',
            'jira_project_category_id' => 20001
        ]);
    }

    #[Test]
    public function it_should_not_store_project_with_missing_data(): void  // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $jiraApiServiceMock->method('fetchProjects')->willReturn([
            'values' => [
                [
                    'id' => 11102,
                    'key' => 'INCOMPLETE',
                    // Falta el campo 'name'
                    'projectCategory' => [
                        'id' => 10001,
                    ],
                ],
            ],
        ]);
        $job = new FetchJiraProjectsJob();

        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseCount('jira_projects', 0);
        $this->assertDatabaseMissing('jira_projects', ['jira_project_key' => 'INCOMPLETE']);
    }


    #[Test]
    public function it_should_not_store_project_with_invalid_category(): void // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $jiraApiServiceMock->method('fetchProjects')->willReturn([
            'values' => [
                [
                    'id' => 11101,
                    'key' => 'INV',
                    'name' => 'Invalido',
                    'projectCategory' => [
                        'id' => 99999,
                    ],
                ],
            ],
        ]);
        $job = new FetchJiraProjectsJob();

        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseCount('jira_projects', 0);
        $this->assertDatabaseMissing('jira_projects', ['jira_project_key' => 'INV']);
    }

    #[Test]
    public function it_should_handle_empty_jira_projects_response(): void // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $jiraApiServiceMock->method('fetchProjects')->willReturn(['values' => []]);

        $job = new FetchJiraProjectsJob();
        $this->seed(JiraProjectCategoriesSeeder::class);

        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseCount('jira_projects', 0);
    }
}
