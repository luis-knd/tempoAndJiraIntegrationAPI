<?php

namespace Tests\Feature\v1\Jira\Jobs;

use App\Jobs\v1\Jira\ProcessJiraIssuesBatchJob;
use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Tempo\TimeEntry;
use App\Services\v1\Tempo\TempoApiService;
use Carbon\Carbon;
use Database\Factories\v1\Jira\JiraIssueFactory;
use Database\Seeders\v1\Jira\JiraIssuesSeeder;
use Database\Seeders\v1\Tempo\TimeEntriesSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class ProcessJiraIssuesBatchJobTest
 *
 * @package   Tests\Feature\v1\Jira\Jobs
 * @copyright 10-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class ProcessJiraIssuesBatchJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function it_should_create_or_update_jira_issues_and_time_entries(): void // phpcs:ignore
    {
        // Given
        $jiraIssue = $this->getOneJiraIssue();
        $timeEntry = $this->getOneTimeEntry();
        $tempoApiServiceMock = $this->createMock(TempoApiService::class);
        $tempoApiServiceMock->method('fetchWorklogs')->willReturn([
            'results' => [
                [
                    'tempoWorklogId' => $timeEntry->tempo_worklog_id,
                    'timeSpentSeconds' => 3600,
                    'createdAt' => Carbon::now()->toDateTimeString(),
                    'updatedAt' => Carbon::now()->toDateTimeString(),
                    'author' => ['accountId' => '123-abc'],
                    'description' => 'Worked on bug fixing.'
                ]
            ]
        ]);
        $issuesBatch = [
            [
                'key' => 'JIRA-1001',
                'fields' => [
                    'summary' => 'Issue Summary',
                    'project' => ['id' => '200'],
                    'customfield_11486' => ['value' => 'Development'],
                    'status' => ['name' => 'In Progress']
                ]
            ]
        ];
        $syncId = 'sync-001';

        // When
        $job = new ProcessJiraIssuesBatchJob($issuesBatch, $syncId);
        $job->handle($tempoApiServiceMock);

        // Then
        $this->assertDatabaseHas('jira_issues', [
            'jira_issue_id' => $jiraIssue->jira_issue_id,
            'jira_issue_key' => 'JIRA-1001',
            'summary' => 'Issue Summary',
            'development_category' => 'Migración tecnológica',
            'status' => 'In Progress'
        ]);
        $this->assertDatabaseHas('time_entries', [
            'tempo_worklog_id' => $timeEntry->tempo_worklog_id,
            'jira_issue_id' => 1001,
            'jira_user_id' => $timeEntry->jira_user_id,
            'time_spent_in_minutes' => 60,
            'description' => 'Worked on bug fixing.'
        ]);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function it_should_skip_issue_if_worklogs_are_empty(): void // phpcs:ignore
    {
        $jiraIssue = $this->getOneJiraIssue();
        $tempoApiServiceMock = $this->createMock(TempoApiService::class);
        $tempoApiServiceMock->method('fetchWorklogs')->willReturn(['results' => []]);

        $issuesBatch = [
            [
                'key' => 'JIRA-1002',
                'fields' => [
                    'summary' => 'Another Issue Summary',
                    'project' => ['id' => '201'],
                    'customfield_11486' => ['value' => 'Testing'],
                    'status' => ['name' => 'To Do']
                ]
            ]
        ];
        $syncId = 'sync-002';

        $job = new ProcessJiraIssuesBatchJob($issuesBatch, $syncId);
        $job->handle($tempoApiServiceMock);

        $this->assertDatabaseHas('jira_issues', [
            'jira_issue_id' => $jiraIssue->jira_issue_id,
            'jira_issue_key' => $jiraIssue->jira_issue_key,
            'summary' => $jiraIssue->summary,
            'development_category' => $jiraIssue->development_category,
            'jira_project_id' => $jiraIssue->jira_project_id,
            'status' => $jiraIssue->status
        ]);

        $this->assertDatabaseCount('time_entries', 0);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function it_should_log_an_exception_when_unexpected_error_occurs(): void // phpcs:ignore
    {
        $jiraIssue = $this->getOneJiraIssue();
        $tempoApiServiceMock = $this->createMock(TempoApiService::class);
        $tempoApiServiceMock->method('fetchWorklogs')->willThrowException(new Exception('Unexpected Error'));

        $issuesBatch = [
            [
                'id' => $jiraIssue->jira_issue_id,
                'key' => $jiraIssue->jira_issue_key,
                'fields' => [
                    'summary' => 'Error Issue Summary',
                    'project' => ['id' => $jiraIssue->jira_project_id],
                    'customfield_11486' => ['value' => 'Bug'],
                    'status' => ['name' => 'In Review']
                ]
            ]
        ];
        $syncId = 'sync-003';

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) use ($syncId) {
                return str_contains(
                    $message,
                    "Exception in ProcessJiraIssuesBatchJob (Sync ID: $syncId):"
                ) && str_contains($message, 'Unexpected Error');
            });

        $job = new ProcessJiraIssuesBatchJob($issuesBatch, $syncId);
        $job->handle($tempoApiServiceMock);
    }


    #[Test]
    public function it_should_return_correct_tags(): void // phpcs:ignore
    {
        $issuesBatch = [
            [
                'id' => '1004',
                'key' => 'JIRA-1004',
                'fields' => [
                    'summary' => 'Tag Issue Summary',
                    'project' => ['id' => '203'],
                    'customfield_11486' => ['value' => 'Feature'],
                    'status' => ['name' => 'Done']
                ]
            ]
        ];
        $syncId = 'sync-004';

        $job = new ProcessJiraIssuesBatchJob($issuesBatch, $syncId);

        $expectedTags = ['jira-sync', 'sync-id:sync-004', 'issue-batch'];
        $this->assertEquals($expectedTags, $job->tags());
    }

    /**
     *  getOneJiraIssue
     *
     * @param int   $count
     * @param array $data
     * @return JiraIssue
     */
    public function getOneJiraIssue(int $count = 1, array $data = []): JiraIssue
    {
        if ($data === []) {
            $data = [
                'jira_issue_id' => 1001,
                'jira_issue_key' => 'JIRA-1001',
                'summary' => 'Issue Summary',
                'development_category' => 'Migración tecnológica',
                'status' => 'In Progress'
            ];
        }
        JiraIssue::factory()->count($count)->create($data);
        return JiraIssue::first();
    }

    /**
     *  getOneTimeEntry
     *
     * @param int   $count
     * @param array $data
     * @return TimeEntry
     */
    public function getOneTimeEntry(int $count = 1, array $data = []): TimeEntry
    {
        if ($data === []) {
            $data = [
                'jira_issue_id' => 1001,
                'time_spent_in_minutes' => 60,
                'description' => 'Worked on bug fixing.'
            ];
        }
        TimeEntry::factory()->count($count)->create($data);
        return TimeEntry::first();
    }
}
