<?php

namespace Tests\Feature\v1\Jira\Jobs;

use App\Jobs\v1\Jira\FetchJiraProjectCategoriesJob;
use App\Services\v1\Jira\JiraApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchJiraProjectCategoriesJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_should_fetch_and_store_jira_categories_from_real_api(): void // phpcs:ignore
    {
        self::markTestSkipped("If you want to test the real API, run this test. And adapt in base for your real data");
        $jiraApiService = resolve(JiraApiService::class);
        $job = new FetchJiraProjectCategoriesJob();

        $job->handle($jiraApiService);

        $this->assertDatabaseHas('jira_project_categories', [
            'jira_category_id' => 10001,
            'name' => 'Track & Trace',
            'description' => 'Proyectos de track and trace'
        ]);
        $this->assertDatabaseCount('jira_project_categories', 8);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function it_should_fetch_and_store_jira_categories_from_mock_api(): void // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);

        $jiraApiServiceMock->method('fetchProjectCategories')
            ->willReturn([
                [
                    'id' => 10001,
                    'name' => 'Track & Trace',
                    'description' => 'Proyectos de track and trace',
                ],
                [
                    'id' => 10002,
                    'name' => 'Otro Proyecto',
                    'description' => 'Proyectos asociados a otro tipo',
                ],
                [
                    'id' => 10003,
                    'name' => 'Calidad',
                    'description' => 'Proyectos asociados a calidad',
                ],
                [
                    'id' => 10004,
                    'name' => 'Comercial',
                    'description' => 'Proyectos asociados a comercial',
                ],
            ]);

        $job = new FetchJiraProjectCategoriesJob();
        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseHas('jira_project_categories', [
            'jira_category_id' => 10001,
            'name' => 'Track & Trace',
            'description' => 'Proyectos de track and trace'
        ]);
        $this->assertDatabaseCount('jira_project_categories', 4);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function it_should_handle_empty_jira_categories_response(): void // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $jiraApiServiceMock->method('fetchProjectCategories')->willReturn([]);

        $job = new FetchJiraProjectCategoriesJob();
        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseCount('jira_project_categories', 0);
    }
}
