<?php

namespace Tests\Feature\v1\Jira\Controllers\ProjectCategory;

use App\Exceptions\JsonResponseAttachment;
use App\Models\v1\Jira\JiraProjectCategory;
use App\Services\v1\Jira\JiraProjectCategoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectCategoryControllerDestroyTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/project-categories';

    #[Test]
    public function an_unauthenticated_user_cannot_delete_a_project_category(): void // phpcs:ignore
    {
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_delete_a_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('delete', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_delete_a_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'message' => 'JiraProjectCategory deleted successfully.',
            'errors' => []
        ]);

        $this->assertSoftDeleted('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
        ]);
    }

    #[Test]
    public function an_authenticated_user_with_explicit_permission_can_delete_a_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        Gate::shouldReceive('authorize')
            ->with('delete', Mockery::any())
            ->andReturn(true);

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/{$jiraProjectCategory->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'message' => 'JiraProjectCategory deleted successfully.',
            'errors' => []
        ]);

        $this->assertSoftDeleted('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_delete_a_non_existent_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $nonExistentCategoryId = 9999;

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$nonExistentCategoryId");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => "Resource $nonExistentCategoryId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_delete_a_soft_deleted_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();
        $jiraProjectCategory->delete();

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => "Resource {$jiraProjectCategory->id} not found.", // @phpstan-ignore-line
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_json_response_attachment_exception_during_delete(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();
        $mockService = Mockery::mock(JiraProjectCategoryService::class);
        $mockService->shouldReceive('delete') // @phpstan-ignore-line
        ->once()
            ->andThrow(new JsonResponseAttachment(
                'Custom error message',
                Response::HTTP_BAD_REQUEST
            ));

        $this->app->instance(JiraProjectCategoryService::class, $mockService);

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/{$jiraProjectCategory->id}");

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson([
            'message' => 'Custom error message',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_concurrent_deletion_attempts(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        // @phpstan-ignore-next-line
        $response1 = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/{$jiraProjectCategory->id}");
        // @phpstan-ignore-next-line
        $response2 = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/{$jiraProjectCategory->id}");

        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response2->assertJsonFragment([
            'message' => "Resource {$jiraProjectCategory->id} not found.", // @phpstan-ignore-line
            'errors' => []
        ]);
    }
}
