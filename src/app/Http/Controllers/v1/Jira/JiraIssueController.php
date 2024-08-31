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
     *  index
     *
     * @param JiraIssueRequest $request
     * @return JsonResponse
     * @throws UnprocessableException
     */
    public function index(JiraIssueRequest $request): JsonResponse
    {
        $params = $request->validated();
        $paginator = $this->jiraIssueService->index($params);

        $jiraIssues = new JiraIssueCollection($paginator);
        return jsonResponse(data: $jiraIssues);
    }

    public function store(JiraIssueRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $jiraIssue = $this->jiraIssueService->make($validatedData);
        return JiraIssueResource::toJsonResponse($jiraIssue);
    }

    /**
     * Handles the HTTP request to display a Jira issue.
     *
     * @param JiraIssueRequest $request
     * @param JiraIssue               $jiraIssue
     * @return JsonResponse
     * @throws UnprocessableException
     */
    public function show(JiraIssueRequest $request, JiraIssue $jiraIssue): JsonResponse
    {
        $params = $request->validated();
        Gate::authorize('view', $jiraIssue);
        $issue = $this->jiraIssueService->load($jiraIssue, $params);
        return JiraIssueResource::toJsonResponse($issue);
    }

    public function update(JiraIssueRequest $request, JiraIssue $jiraIssue): JsonResponse
    {
        Gate::authorize('update', $jiraIssue);
        $params = $request->validated();
        $updatedIssue = $this->jiraIssueService->update($jiraIssue, $params);
        return JiraIssueResource::toJsonResponse($updatedIssue);
    }

    public function destroy(JiraIssueRequest $request, JiraIssue $jiraIssue): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $jiraIssue);
        $this->jiraIssueService->delete($jiraIssue);
        return jsonResponse(message: 'JiraIssue deleted successfully.');
    }
}
