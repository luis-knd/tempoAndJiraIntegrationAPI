<?php

namespace App\Http\Controllers\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Jira\JiraProjectCategoryRequest;
use App\Http\Resources\v1\Jira\JiraProjectCategoryCollection;
use App\Http\Resources\v1\Jira\JiraProjectCategoryResource;
use App\Models\v1\Jira\JiraProjectCategory;
use App\Services\v1\Jira\JiraProjectCategoryService;
use Illuminate\Http\JsonResponse;
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
     * @param \App\Http\Requests\v1\Jira\JiraProjectCategoryRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BadRequestException
     * @throws \JsonException
     */
    public function index(JiraProjectCategoryRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $paginator = $this->jiraProjectCategoryService->index($params);
            $categories = new JiraProjectCategoryCollection($paginator);
            return jsonResponse(data: $categories);
        } catch (UnprocessableException $e) {
            $errorMessage = $e->getMessage();
            return jsonResponse(
                status: $e->getCode(),
                message: $errorMessage,
                errors: ['params' => $errorMessage]
            );
        }
    }

    public function store(JiraProjectCategoryRequest $request): JsonResponse
    {
        $category = $request->validated();
        return JiraProjectCategoryResource::toJsonResponse(
            $this->jiraProjectCategoryService->make($category)
        );
    }

    /**
     *  show
     *
     * @param JiraProjectCategoryRequest $request
     * @param JiraProjectCategory        $category
     * @return JsonResponse
     * @throws JsonException
     */
    public function show(JiraProjectCategoryRequest $request, JiraProjectCategory $category): JsonResponse
    {
        try {
            $params = $request->validated();
            return JiraProjectCategoryResource::toJsonResponse(
                $this->jiraProjectCategoryService->load($category, $params)
            );
        } catch (UnprocessableException $e) {
            $errorMessage = $e->getMessage();
            return jsonResponse(
                status: $e->getCode(),
                message: $errorMessage,
                errors: ['params' => $errorMessage]
            );
        }
    }

    public function update(JiraProjectCategoryRequest $request, JiraProjectCategory $category): JsonResponse
    {
        $updatedCategory = $this->jiraProjectCategoryService->update($category, $request->validated());
        return JiraProjectCategoryResource::toJsonResponse($updatedCategory);
    }

    public function destroy(JiraProjectCategoryRequest $request, JiraProjectCategory $category): JsonResponse
    {
        $request->validated();
        $this->jiraProjectCategoryService->delete($category);
        return jsonResponse(message: 'JiraProjectCategory deleted successfully.');
    }
}
