<?php

namespace Tests\Feature\v1\Jira\Jobs;

use App\Jobs\v1\Jira\FetchJiraIssuesJob;
use App\Jobs\v1\Jira\FetchJiraProjectsJob;
use App\Jobs\v1\Jira\FetchJiraUsersJob;
use App\Jobs\v1\Jira\ProcessJiraIssuesBatchJob;
use App\Services\v1\Jira\JiraApiService;
use Database\Seeders\v1\Jira\JiraProjectCategoriesSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FetchJiraIssuesJobTest extends TestCase
{
    use RefreshDatabase;

    private string $jql;
    protected JiraApiService|MockObject $jiraApiServiceMock;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->jql = 'filter = "Issues 2.0"';
        $this->jiraApiServiceMock = $this->createMock(JiraApiService::class);
    }

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
        $jiraIssuesJob = new FetchJiraIssuesJob($this->jql, $syncId);

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

    #[Test]
    public function it_should_handle_empty_jira_issues_response(): void // phpcs:ignore
    {
        $this->jiraApiServiceMock->method('fetchIssueByJQL')->willReturn([]);
        $job = new FetchJiraIssuesJob($this->jql, 'sync-id');
        $job->handle($this->jiraApiServiceMock);
        $this->assertDatabaseCount('jira_issues', 0);
    }


    #[Test]
    public function it_should_fetch_and_process_jira_issues_in_batches(): void // phpcs:ignore
    {
        Queue::fake();
        $this->app->instance(JiraApiService::class, $this->jiraApiServiceMock);
        $jql = 'project = TEST';
        $syncId = 'test-sync-id';
        $totalIssues = 120;

        $this->jiraApiServiceMock->expects($this->exactly(3))
            ->method('fetchIssueByJQL')
            ->willReturnOnConsecutiveCalls(
                $this->createMockResponse(0, 50, $totalIssues),
                $this->createMockResponse(50, 50, $totalIssues),
                $this->createMockResponse(100, 20, $totalIssues)
            );

        $job = new FetchJiraIssuesJob($jql, $syncId);
        $job->handle($this->jiraApiServiceMock);

        Queue::assertPushed(ProcessJiraIssuesBatchJob::class, 3);
        Queue::assertPushed(ProcessJiraIssuesBatchJob::class, static function ($job) use ($syncId) {
            return $job->getSyncId() === $syncId && count($job->getIssuesBatch()) === 50;
        });
    }

    private function createMockResponse(int $startAt, int $issueCount, int $totalIssues): array
    {
        $issues = array_fill(0, $issueCount, [
            'id' => 'TEST-' . ($startAt + 1),
            'key' => 'TEST-' . ($startAt + 1),
            'fields' => [
                'summary' => 'Test Issue ' . ($startAt + 1),
                'status' => ['name' => 'Open'],
                'project' => ['id' => 'TEST'],
                'customfield_11486' => ['value' => 'Test Category']
            ]
        ]);

        return [
            'issues' => $issues,
            'startAt' => $startAt,
            'maxResults' => 50,
            'total' => $totalIssues
        ];
    }

    #[Test]
    public function it_should_return_correct_jql(): void // phpcs:ignore
    {
        $jql = 'project = TEST';
        $syncId = 'test-sync-id';

        $job = new FetchJiraIssuesJob($jql, $syncId);
        $returnedJql = $job->getJql();

        $this->assertEquals($jql, $returnedJql);
    }

    #[Test]
    public function it_should_return_correct_tags(): void // phpcs:ignore
    {
        $jql = 'project = TEST';
        $syncId = 'test-sync-id';

        $job = new FetchJiraIssuesJob($jql, $syncId);
        $tags = $job->tags();

        $this->assertEquals(['jira-sync', 'sync-id:test-sync-id'], $tags);
    }

    #[Test]
    public function it_should_handle_api_exception(): void // phpcs:ignore
    {
        Queue::fake();

        $jql = 'project = ERROR';
        $syncId = 'error-sync-id';

        $this->jiraApiServiceMock->method('fetchIssueByJQL')
            ->willThrowException(new Exception('API Error'));

        $job = new FetchJiraIssuesJob($jql, $syncId);
        $job->handle($this->jiraApiServiceMock);

        Queue::assertNotPushed(ProcessJiraIssuesBatchJob::class);
        $this->assertLogContains("Exception in FetchJiraIssuesJob (Sync ID: $syncId): API Error");
    }

    private function assertLogContains(string $message): void
    {
        $this->assertStringContainsString($message, file_get_contents(storage_path('logs/laravel.log')));
    }
}
