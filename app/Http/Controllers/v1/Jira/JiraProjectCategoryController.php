<?php

namespace App\Http\Controllers\v1\Jira;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Jira\JiraProjectCategoryRequest;
use App\Http\Resources\v1\Jira\JiraProjectCategoryCollection;
use App\Http\Resources\v1\Jira\JiraProjectCategoryResource;
use App\Models\v1\Jira\JiraProjectCategory;
use App\Services\v1\Jira\JiraProjectCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use JsonException;

class JiraProjectCategoryController extends Controller
{
    private JiraProjectCategoryService $jiraProjectCategoryService;

    public function __construct(JiraProjectCategoryService $jiraProjectCategoryService)
    {
        $this->jiraProjectCategoryService = $jiraProjectCategoryService;
    }

    /**
     *  index
     *
     * @param JiraProjectCategoryRequest $request
     * @return JsonResponse
     * @throws BadRequestException
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function index(JiraProjectCategoryRequest $request): JsonResponse
    {
            $params = $request->validated();
            $paginator = $this->jiraProjectCategoryService->index($params);
            $categories = new JiraProjectCategoryCollection($paginator);
            return jsonResponse(data: $categories);
    }

    public function store(JiraProjectCategoryRequest $request): JsonResponse
    {
        $project_category = $request->validated();
        Gate::authorize('create', JiraProjectCategory::class);
        return JiraProjectCategoryResource::toJsonResponse(
            $this->jiraProjectCategoryService->make($project_category)
        );
    }

    /**
     *  show
     *
     * @param JiraProjectCategoryRequest $request
     * @param JiraProjectCategory        $project_category
     * @return JsonResponse
     * @throws JsonException
     * @throws UnprocessableException
     */
    public function show(JiraProjectCategoryRequest $request, JiraProjectCategory $project_category): JsonResponse
    {
        $params = $request->validated();
        Gate::authorize('view', $project_category);
        return JiraProjectCategoryResource::toJsonResponse(
            $this->jiraProjectCategoryService->load($project_category, $params)
        );
    }

    /**
     *  update
     *
     * @param JiraProjectCategoryRequest $request
     * @param JiraProjectCategory        $project_category
     * @return JsonResponse
     */
    public function update(JiraProjectCategoryRequest $request, JiraProjectCategory $project_category): JsonResponse
    {
        Gate::authorize('update', $project_category);
        $updatedCategory = $this->jiraProjectCategoryService->update($project_category, $request->validated());
        return JiraProjectCategoryResource::toJsonResponse($updatedCategory);
    }

    /**
     *  destroy
     *
     * @param JiraProjectCategoryRequest $request
     * @param JiraProjectCategory        $project_category
     * @return JsonResponse
     */
    public function destroy(JiraProjectCategoryRequest $request, JiraProjectCategory $project_category): JsonResponse
    {
        Gate::authorize('delete', $project_category);
        $request->validated();
        $this->jiraProjectCategoryService->delete($project_category);
        return jsonResponse(message: 'JiraProjectCategory deleted successfully.');
    }
}
