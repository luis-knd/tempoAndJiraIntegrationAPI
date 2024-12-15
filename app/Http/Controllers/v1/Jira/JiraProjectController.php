<?php

namespace App\Http\Controllers\v1\Jira;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Auth\v1\Jira\JiraProjectRequest;
use App\Http\Resources\v1\Jira\JiraProjectCollection;
use App\Http\Resources\v1\Jira\JiraProjectResource;
use App\Models\v1\Jira\JiraProject;
use App\Services\v1\Jira\JiraProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use JsonException;

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
     * @return JsonResponse
     * @throws BadRequestException
     * @throws UnprocessableException
     * @throws JsonException
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
        Gate::authorize('create', JiraProject::class);
        $project = $this->jiraProjectService->make($validatedData);
        return JiraProjectResource::toJsonResponse($project);
    }

    /**
     * show
     *
     * Handles the HTTP request to display a Jira project.
     *
     * @param JiraProjectRequest $request
     * @param JiraProject        $project
     * @return JsonResponse
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function show(JiraProjectRequest $request, JiraProject $project): JsonResponse
    {
        $params = $request->validated();
        Gate::authorize('view', $project);
        return JiraProjectResource::toJsonResponse(
            $this->jiraProjectService->load($project, $params)
        );
    }

    /**
     *  update
     *
     * @param JiraProjectRequest $request
     * @param JiraProject        $project
     * @return JsonResponse
     */
    public function update(JiraProjectRequest $request, JiraProject $project): JsonResponse
    {
        Gate::authorize('update', $project);
        $params = $request->validated();
        $updatedProject = $this->jiraProjectService->update($project, $params);
        return JiraProjectResource::toJsonResponse($updatedProject);
    }

    /**
     *  destroy
     *
     * @param JiraProjectRequest $request
     * @param JiraProject        $project
     * @return JsonResponse
     */
    public function destroy(JiraProjectRequest $request, JiraProject $project): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $project);
        $this->jiraProjectService->delete($project);
        return jsonResponse(message: 'JiraProject deleted successfully.');
    }
}
