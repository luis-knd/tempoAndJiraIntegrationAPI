<?php

namespace App\Http\Controllers\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Jira\JiraUserRequest;
use App\Http\Resources\v1\Jira\JiraUserCollection;
use App\Http\Resources\v1\Jira\JiraUserResource;
use App\Models\v1\Jira\JiraUser;
use App\Services\v1\Jira\JiraUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use JsonException;

/**
 * Class JiraUserController
 *
 * @package   App\Http\Controllers\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraUserController extends Controller
{
    private JiraUserService $jiraUserService;

    public function __construct(JiraUserService $jiraUserService)
    {
        $this->jiraUserService = $jiraUserService;
    }


    /**
     *  index
     *
     * @param JiraUserRequest $request
     * @return JsonResponse
     * @throws JsonException
     */
    public function index(JiraUserRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $paginator = $this->jiraUserService->index($params);

            $jiraUsers = new JiraUserCollection($paginator);
            return jsonResponse(data: $jiraUsers);
        } catch (UnprocessableException $e) {
            $errorMessage = $e->getMessage();
            return jsonResponse(
                status: $e->getCode(),
                message: $errorMessage,
                errors: ['params' => $errorMessage]
            );
        }
    }


    public function store(JiraUserRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $jiraUser = $this->jiraUserService->make($validatedData);
        return JiraUserResource::toJsonResponse($jiraUser);
    }

    /**
     *  show
     *
     * @param JiraUserRequest $request
     * @param JiraUser        $jiraUser
     * @return JsonResponse
     * @throws \JsonException
     */
    public function show(JiraUserRequest $request, JiraUser $jiraUser): JsonResponse
    {
        try {
            $params = $request->validated();
            Gate::authorize('view', $jiraUser);
            $user = $this->jiraUserService->load($jiraUser, $params);
            return JiraUserResource::toJsonResponse($user);
        } catch (UnprocessableException $e) {
            $errorMessage = $e->getMessage();
            return jsonResponse(
                status: $e->getCode(),
                message: $errorMessage,
                errors: ['params' => $errorMessage]
            );
        }
    }

    public function update(JiraUserRequest $request, JiraUser $jiraUser): JsonResponse
    {
        Gate::authorize('update', $jiraUser);
        $params = $request->validated();
        $updatedUser = $this->jiraUserService->update($jiraUser, $params);
        return JiraUserResource::toJsonResponse($updatedUser);
    }

    public function destroy(JiraUserRequest $request, JiraUser $jiraUser): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $jiraUser);
        $this->jiraUserService->delete($jiraUser);
        return jsonResponse(message: 'JiraUser deleted successfully.');
    }
}
