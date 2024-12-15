<?php

namespace App\Http\Controllers\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Auth\v1\Jira\JiraTeamRequest;
use App\Http\Resources\v1\Jira\JiraTeamCollection;
use App\Http\Resources\v1\Jira\JiraTeamResource;
use App\Models\v1\Jira\JiraTeam;
use App\Services\v1\Jira\JiraTeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use JsonException;

/**
 * Class JiraTeamController
 *
 * @package   App\Http\Controllers\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraTeamController extends Controller
{
    private JiraTeamService $jiraTeamService;

    public function __construct(JiraTeamService $jiraTeamService)
    {
        $this->jiraTeamService = $jiraTeamService;
    }

    /**
     *  index
     *
     * @param JiraTeamRequest $request
     * @return JsonResponse
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function index(JiraTeamRequest $request): JsonResponse
    {
        $params = $request->validated();
        $paginator = $this->jiraTeamService->index($params);

        $jiraTeams = new JiraTeamCollection($paginator);
        return jsonResponse(data: $jiraTeams);
    }

    public function store(JiraTeamRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        Gate::authorize('create', JiraTeam::class);
        $jiraTeam = $this->jiraTeamService->make($validatedData);
        return JiraTeamResource::toJsonResponse($jiraTeam);
    }

    /**
     *  show
     *
     * @param JiraTeamRequest $request
     * @param JiraTeam               $team
     * @return JsonResponse
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function show(JiraTeamRequest $request, JiraTeam $team): JsonResponse
    {
        $params = $request->validated();
        Gate::authorize('view', $team);
        $team = $this->jiraTeamService->load($team, $params);
        return JiraTeamResource::toJsonResponse($team);
    }

    public function update(JiraTeamRequest $request, JiraTeam $team): JsonResponse
    {
        Gate::authorize('update', $team);
        $params = $request->validated();
        $updatedTeam = $this->jiraTeamService->update($team, $params);
        return JiraTeamResource::toJsonResponse($updatedTeam);
    }

    public function destroy(JiraTeamRequest $request, JiraTeam $team): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $team);
        $this->jiraTeamService->delete($team);
        return jsonResponse(message: 'JiraTeam deleted successfully.');
    }
}
