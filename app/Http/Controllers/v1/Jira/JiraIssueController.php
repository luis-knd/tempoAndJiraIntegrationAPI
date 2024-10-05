<?php

namespace App\Http\Controllers\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Jira\JiraIssueRequest;
use App\Http\Resources\v1\Jira\JiraIssueCollection;
use App\Http\Resources\v1\Jira\JiraIssueResource;
use App\Models\v1\Jira\JiraIssue;
use App\Services\v1\Jira\JiraIssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use JsonException;

/**
 * Class JiraIssueController
 *
 * @package   App\Http\Controllers\v1\Jira
 * @copyright 08-2024 Lcandesign
 */
class JiraIssueController extends Controller
{
    private JiraIssueService $jiraIssueService;

    public function __construct(JiraIssueService $jiraIssueService)
    {
        $this->jiraIssueService = $jiraIssueService;
    }

    /**
     * Retrieves a paginated list of Jira issues.
     *
     * This endpoint fetches a list of Jira issues based on the filters and parameters provided in the request. The data
     * is paginated and returned as a collection of Jira issues.
     *
     * @param JiraIssueRequest $request The incoming request containing filters like project ID, status, or assignee.
     *
     * @return JsonResponse A JSON response containing the paginated list of Jira issues.
     *
     * @throws JsonException If the response cannot be properly encoded to JSON.
     * @response array{
     *   "data": array{
     *      "key" : array{
     *          "id": "12345",
     *          "key": "PROJ-1",
     *          "summary": "Issue summary",
     *          "status": "Open"
     *      },
     *   },
     *   "meta": array{
     *     "pagination": array{
     *       "total": 100,
     *       "count": 10,
     *       "per_page": 10,
     *       "current_page": 1,
     *       "total_pages": 10
     *     }
     *   }
     * }
     */
    public function index(JiraIssueRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $paginator = $this->jiraIssueService->index($params);
            $jiraIssues = new JiraIssueCollection($paginator);
            return jsonResponse(data: $jiraIssues);
        } catch (UnprocessableException $e) {
            $errorMessage = $e->getMessage();
            return jsonResponse(
                status: $e->getCode(),
                message: $errorMessage,
                errors: ['params' => $errorMessage]
            );
        }
    }

    /**
     * Creates a new Jira issue.
     *
     * This endpoint allows the creation of a new Jira issue using the data provided in the request. The data is
     * validated, and the issue is then created and returned in the response.
     *
     * @param JiraIssueRequest $request The incoming request containing the details of the Jira issue to be created,
     *                                  such as project ID, issue type, and description.
     *
     * @return JsonResponse A JSON response containing the newly created Jira issue.
     *
     * @response array{
     *   "data": array{
     *     "id": "12345",
     *     "key": "PROJ-1",
     *     "summary": "Issue summary",
     *     "status": "Open"
     *   }
     * }
     */
    public function store(JiraIssueRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        Gate::authorize('create', JiraIssue::class);
        $jiraIssue = $this->jiraIssueService->make($validatedData);
        return JiraIssueResource::toJsonResponse($jiraIssue);
    }

    /**
     * Displays a specific Jira issue.
     *
     * This method retrieves the details of a specific Jira issue based on its ID. The user must be authorized
     * to view the issue. It uses the `JiraIssueService` to load the issue details and returns the data as a JSON
     * response.
     *
     * @param JiraIssueRequest $request   The validated HTTP request containing any additional parameters for loading
     *                                    the issue.
     * @param JiraIssue        $issue     The Jira issue to be displayed.
     *
     * @return JsonResponse A JSON response containing the details of the Jira issue.
     *
     * @response array{
     *      "data": array{
     *          "id": "9c77677e-2ceb-4f21-8773-40f89cd17247",
     *          "title": "Sample Jira Issue",
     *          "status": "In Progress",
     *          "assignee": "John Doe",
     *          "created_at": "2024-09-22T14:30:00Z"
     *      }
     * }
     *
     * @throws JsonException If the response cannot be properly encoded to JSON.
     */
    public function show(JiraIssueRequest $request, JiraIssue $issue): JsonResponse
    {

        try {
            $params = $request->validated();
            Gate::authorize('view', $issue);
            $issue = $this->jiraIssueService->load($issue, $params);
            return JiraIssueResource::toJsonResponse($issue);
        } catch (UnprocessableException $e) {
            $errorMessage = $e->getMessage();
            return jsonResponse(
                status: $e->getCode(),
                message: $errorMessage,
                errors: ['params' => $errorMessage]
            );
        }
    }

    /**
     * Updates an existing Jira issue.
     *
     * This endpoint allows for the modification of an existing Jira issue. The data provided in the request is
     * validated, and the issue is updated with the new information. Authorization is required to update the issue.
     *
     * @param JiraIssueRequest $request The incoming request containing the updated data for the Jira issue.
     * @param JiraIssue        $issue   The Jira issue to be updated.
     *
     * @return JsonResponse A JSON response containing the updated Jira issue.
     *
     * @response array{
     *    "data": array{
     *      "id": "12345",
     *      "key": "PROJ-1",
     *      "summary": "Issue summary",
     *      "status": "Open"
     *    }
     *  }
     */
    public function update(JiraIssueRequest $request, JiraIssue $issue): JsonResponse
    {
        Gate::authorize('update', $issue);
        $params = $request->validated();
        $updatedIssue = $this->jiraIssueService->update($issue, $params);
        return JiraIssueResource::toJsonResponse($updatedIssue);
    }

    /**
     * Deletes a Jira issue.
     *
     * This method handles the deletion of a specific Jira issue. The user must be authorized to delete the issue.
     * It uses the `JiraIssueService` to perform the deletion and returns a confirmation message in the JSON response.
     *
     * @param JiraIssueRequest $request The validated HTTP request.
     * @param JiraIssue        $issue   The Jira issue to be deleted.
     *
     * @return JsonResponse A JSON response confirming the deletion of the Jira issue.
     *
     * @response array{
     *   "message": "JiraIssue deleted successfully."
     * }
     */

    public function destroy(JiraIssueRequest $request, JiraIssue $issue): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $issue);
        $this->jiraIssueService->delete($issue);
        return jsonResponse(message: 'JiraIssue deleted successfully.');
    }
}
