<?php

namespace App\Http\Controllers\v1\Jira;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Jira\JiraSyncRequest;
use App\Jobs\v1\Jira\FetchJiraIssuesJob;
use App\Jobs\v1\Jira\FetchJiraProjectCategoriesJob;
use App\Jobs\v1\Jira\FetchJiraProjectsJob;
use App\Jobs\v1\Jira\FetchJiraUsersJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Class JiraSyncController
 *
 * @package   App\Http\Controllers\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraSyncController extends Controller
{
    /**
     * Initiates a full synchronization of Jira data, including project categories, projects, users, and issues.
     *
     * This endpoint starts the process of synchronizing data from Jira by dispatching several background jobs
     * that fetch Jira project categories, projects, and users. Additionally, it synchronizes Jira issues based
     * on the request data. The response indicates that the synchronization process has started.
     *
     * @param JiraSyncRequest $request The incoming HTTP request containing any necessary data for issue
     *                                 synchronization. This could include filters or parameters used in the
     *                                 syncJiraIssues` method.
     *
     * @return JsonResponse A JSON response indicating that the Jira synchronization process has been initiated.
     *
     * @response 200 array{
     *   "message": "Jira sync started"
     * }
     */
    public function syncAll(JiraSyncRequest $request): JsonResponse
    {
        FetchJiraProjectCategoriesJob::dispatch();
        FetchJiraProjectsJob::dispatch();
        FetchJiraUsersJob::dispatch();
        $this->syncJiraIssues($request);
        return response()->json(['message' => 'Jira categories, projects, users, issues and worklogs sync started']);
    }

    /**
     * Synchronizes Jira issues based on the provided request data.
     *
     * This endpoint triggers the synchronization process for Jira issues. It uses the data provided in the request
     * to determine which issues to synchronize. The actual synchronization logic is handled by the `syncJiraIssues`
     * method, which could involve filtering issues based on specific parameters.
     *
     * @param JiraSyncRequest $request The incoming HTTP request, which may include parameters such as project IDs,
     *                                 issue types, or other filters to determine which Jira issues to synchronize.
     *
     * @return JsonResponse A JSON response indicating that the Jira issue synchronization process has started.
     *
     * @response 200 array{
     *   "message": "Jira sync issues started"
     * }
     *
     */

    public function syncIssues(JiraSyncRequest $request): JsonResponse
    {
        $this->syncJiraIssues($request);
        return response()->json(['message' => 'Jira issues sync started']);
    }

    /**
     * Synchronizes Jira issues based on JQL or default query.
     *
     * This method processes the synchronization of Jira issues using a JQL (Jira Query Language) string provided
     * in the request. If no JQL is provided, a default query is used to fetch issues created since the start of the
     * current month. The method dispatches a background job to handle the actual synchronization.
     *
     * @param JiraSyncRequest $request The incoming HTTP request that has been validated, containing optional JQL query
     *                                 parameters. The request may include a 'jql' parameter for custom filtering of
     *                                 issues.
     *
     * @return void This method does not return a value. It dispatches a background job for issue synchronization.
     * @example request {
     *          "jql": "created >= 2024-08-01 AND created < 2024-09-23"
     *          }
     *
     */
    public function syncJiraIssues(JiraSyncRequest $request): void
    {
        $parameters = $request->validated();
        $syncId = Str::uuid()->toString();
        FetchJiraIssuesJob::dispatch($parameters['jql'], $syncId);
    }
}
