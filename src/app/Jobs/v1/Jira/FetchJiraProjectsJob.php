<?php

namespace App\Jobs\v1\Jira;

use App\Exceptions\BadRequestException;
use App\Models\v1\Jira\JiraProject;
use App\Services\v1\Jira\JiraApiService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FetchJiraProjectsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    /**
     *  handle
     *
     * @param JiraApiService $jiraApiService
     * @return void
     */
    public function handle(JiraApiService $jiraApiService): void
    {
        try {
            $startAt = 0;
            $maxResults = 50;
            $projectValues = [];
            do {
                $projects = $jiraApiService->fetchProjects(['startAt' => $startAt, 'maxResults' => $maxResults]);
                if (isset($projects['values'])) {
                    $projectValues = array_merge($projects['values'], $projectValues);
                }
                $startAt += $maxResults;
            } while (isset($projects['nextPage']));

            foreach ($projectValues as $project) {
                if (!isset($project['id'], $project['key'], $project['name'])) {
                    throw new BadRequestException('Invalid project data', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                JiraProject::updateOrCreate(
                    ['jira_project_id' => $project['id']],
                    [
                        'jira_project_key' => $project['key'],
                        'name' => $project['name'],
                        'jira_project_category_id' => isset($project['projectCategory']['id']) ?
                            (int)$project['projectCategory']['id'] : null,
                    ]
                );
            }
        } catch (BadRequestException $e) {
            Log::error("BadRequestException in FetchJiraProjectsJob: " . $e->getMessage());
        } catch (Exception $e) {
            Log::error("Exception in FetchJiraProjectsJob: " . $e->getMessage());
        }
    }
}
