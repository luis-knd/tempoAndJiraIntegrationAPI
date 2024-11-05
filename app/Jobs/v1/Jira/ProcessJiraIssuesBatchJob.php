<?php

namespace App\Jobs\v1\Jira;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Tempo\TimeEntry;
use App\Services\v1\Tempo\TempoApiService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class ProcessJiraIssuesBatchJob
 *
 * @package   App\Jobs\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class ProcessJiraIssuesBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected array $issuesBatch;
    protected string $syncId;

    public function __construct(array $issuesBatch, string $syncId)
    {
        $this->issuesBatch = $issuesBatch;
        $this->syncId = $syncId;
    }

    public function handle(TempoApiService $tempoApiService): void
    {
        try {
            ini_set('memory_limit', '2048M');
            foreach ($this->issuesBatch as $issueData) {
                $projectId = $issueData['fields']['project']['id'] ?? null;
                $developmentCategory = $issueData['fields']['customfield_11486']['value'] ?? 'Sin categoría asignada';

                JiraIssue::updateOrCreate(
                    ['jira_issue_id' => (int)$issueData['id']],
                    [
                        'jira_issue_key' => $issueData['key'],
                        'summary' => $issueData['fields']['summary'],
                        'development_category' => $developmentCategory,
                        'jira_project_id' => $projectId,
                        'status' => $issueData['fields']['status']['name']
                    ]
                );

                $worklogs = $tempoApiService->fetchWorklogs((int)$issueData['id']);
                if (isset($worklogs['results']) && is_array($worklogs['results']) && count($worklogs['results']) > 0) {
                    foreach ($worklogs['results'] as $worklogData) {
                        $timeSpentInMinutes = $worklogData['timeSpentSeconds'] ?
                            $worklogData['timeSpentSeconds'] / 60 : 0;

                        $entryCreatedAt = Carbon::parse($worklogData['createdAt'])->toDateTimeString();
                        $entryUpdatedAt = Carbon::parse($worklogData['updatedAt'])->toDateTimeString();

                        TimeEntry::updateOrCreate(
                            ['tempo_worklog_id' => (int)$worklogData['tempoWorklogId']],
                            [
                                'jira_issue_id' => (int)$issueData['id'],
                                'jira_user_id' => $worklogData['author']['accountId'],
                                'time_spent_in_minutes' => $timeSpentInMinutes,
                                'description' => $worklogData['description'] ?? '',
                                'entry_created_at' => $entryCreatedAt,
                                'entry_updated_at' => $entryUpdatedAt
                            ]
                        );
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("Exception in ProcessJiraIssuesBatchJob (Sync ID: $this->syncId): {$e->getMessage()}");
        }
    }

    public function tags(): array
    {
        return ['jira-sync', 'sync-id:' . $this->syncId, 'issue-batch'];
    }

    public function getSyncId(): string
    {
        return $this->syncId;
    }

    public function getIssuesBatch(): array
    {
        return $this->issuesBatch;
    }
}
