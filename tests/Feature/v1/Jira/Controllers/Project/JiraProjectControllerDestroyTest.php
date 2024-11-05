<?php

namespace Tests\Feature\v1\Jira\Controllers\Project;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraProject;
use App\Services\v1\Jira\JiraProjectService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectControllerDestroyTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/projects';

    #[Test]
    public function an_unauthenticated_user_cannot_delete_a_project(): void // phpcs:ignore
    {
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$project->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_delete_a_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$project->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'message' => 'JiraProject deleted successfully.',
            'status' => Response::HTTP_OK,
            'errors' => []
        ]);
        $this->assertDatabaseMissing('jira_projects', ['id' => $project->id]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_delete_a_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('delete', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$project->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_unprocessable_exception_during_deletion(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();
        $mockService = Mockery::mock(JiraProjectService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('delete')
            ->with(Mockery::on(static function ($arg) use ($project) {
                return $arg->is($project);
            }))
            ->andThrow(
                new UnprocessableException(
                    "Unable to delete project due to invalid state.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        $this->app->instance(JiraProjectService::class, $mockService);

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$project->id");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'Unable to delete project due to invalid state.',
            'errors' => [
                'params' => 'Unable to delete project due to invalid state.'
            ]
        ]);
    }
}
