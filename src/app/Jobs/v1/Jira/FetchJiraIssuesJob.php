<?php

namespace App\Jobs\v1\Jira;

use App\Services\v1\Jira\JiraApiService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchJiraIssuesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;
    protected string $jql;
    private int $batchSize = 50;
    private string $syncId;

    public function __construct(string $jql, string $syncId)
    {
        $this->jql = $jql;
        $this->syncId = $syncId;
    }

    public function handle(JiraApiService $jiraApiService): void
    {
        try {
            ini_set('memory_limit', '2048M');
            $startAt = 0;
            $totalIssues = 0;
            do {
                $issues = $jiraApiService->fetchIssueByJQL([
                    'jql' => $this->jql,
                    'startAt' => $startAt,
                    'maxResults' => $this->batchSize
                ]);

                if (!empty($issues['issues'])) {
                    ProcessJiraIssuesBatchJob::dispatch($issues['issues'], $this->syncId);
                }
                if ($startAt === 0 && isset($issues['total'])) {
                    $totalIssues = $issues['total'];
                }

                $startAt += $this->batchSize;
                unset($issues);
                gc_collect_cycles();
            } while ($startAt < $totalIssues);
        } catch (Exception $e) {
            Log::error("Exception in FetchJiraIssuesJob (Sync ID: $this->syncId): {$e->getMessage()}");
        }
    }

    public function tags(): array
    {
        return ['jira-sync', 'sync-id:' . $this->syncId];
    }

    public function getJql(): string
    {
        return $this->jql;
    }
}
