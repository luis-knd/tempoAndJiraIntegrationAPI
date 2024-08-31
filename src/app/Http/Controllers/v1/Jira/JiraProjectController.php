<?php

namespace App\Http\Controllers\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Jira\JiraProjectRequest;
use App\Http\Resources\v1\Jira\JiraProjectCollection;
use App\Http\Resources\v1\Jira\JiraProjectResource;
use App\Models\v1\Jira\JiraProject;
use App\Services\v1\Jira\JiraProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Class JiraProjectController
 *
 * @package   App\Http\Controllers\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraProjectController extends Controller
{
    private JiraProjectService $jiraProjectService;

    public function __construct(JiraProjectService $jiraProjectService)
    {
        $this->jiraProjectService = $jiraProjectService;
    }

    /**
     *  index
     *
     * @param JiraProjectRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\UnprocessableException
     */
    public function index(JiraProjectRequest $request): JsonResponse
    {
        $params = $request->validated();
        $paginator = $this->jiraProjectService->index($params);

        $jiraProjects = new JiraProjectCollection($paginator);
        return jsonResponse(data: $jiraProjects);
    }

    public function store(JiraProjectRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $jiraProject = $this->jiraProjectService->make($validatedData);
        return JiraProjectResource::toJsonResponse($jiraProject);
    }

    /**
     *  show
     *
     * Handles the HTTP request to display a Jira project.
     *
     * @param JiraProjectRequest $request
     * @param JiraProject        $jiraProject
     * @return JsonResponse
     * @throws UnprocessableException
     */
    public function show(JiraProjectRequest $request, JiraProject $jiraProject): JsonResponse
    {
        $params = $request->validated();
        Gate::authorize('view', $jiraProject);
        $project = $this->jiraProjectService->load($jiraProject, $params);
        return JiraProjectResource::toJsonResponse($project);
    }

    public function update(JiraProjectRequest $request, JiraProject $jiraProject): JsonResponse
    {
        Gate::authorize('update', $jiraProject);
        $params = $request->validated();
        $updatedProject = $this->jiraProjectService->update($jiraProject, $params);
        return JiraProjectResource::toJsonResponse($updatedProject);
    }

    public function destroy(JiraProjectRequest $request, JiraProject $jiraProject): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $jiraProject);
        $this->jiraProjectService->delete($jiraProject);
        return jsonResponse(message: 'JiraProject deleted successfully.');
    }
}
