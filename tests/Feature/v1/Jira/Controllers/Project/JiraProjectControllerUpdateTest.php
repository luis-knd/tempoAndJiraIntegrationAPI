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

class JiraProjectControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/projects';

    #[Test]
    public function an_unauthenticated_user_cannot_update_a_project(): void // phpcs:ignore
    {
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();
        $updateData = [
            'name' => 'Updated Project Name',
            'jira_project_key' => 'UPDKEY'
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$project->id", $updateData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_update_a_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();
        $updateData = [
            'name' => 'Updated Project Name',
            'jira_project_key' => 'UPDKEY',
            'jira_project_category_id' => $project->jira_project_category_id
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$project->id", $updateData);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project' => [
                    'jira_project_id' => $project->jira_project_id,
                    'name' => 'Updated Project Name',
                    'jira_project_key' => 'UPDKEY',
                    'category' => [
                        'jira_category_id' => $project->jira_project_category_id
                    ]
                ]
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
        $this->assertDatabaseHas('jira_projects', $updateData);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_update_a_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('update', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $updateData = [
            'name' => 'Unauthorized Update',
            'jira_project_key' => 'UNAUTHKEY',
            'jira_project_category_id' => $project->jira_project_category_id
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$project->id", $updateData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_returns_validation_errors_for_invalid_update_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();
        $invalidData = [
            'jira_project_key' => '',
            'name' => null,
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$project->id", $invalidData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['jira_project_key', 'name']);
    }

    #[Test]
    public function it_handles_unprocessable_exception_during_update(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $project */
        $project = JiraProject::factory()->create();
        $mockService = Mockery::mock(JiraProjectService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('update')
            ->with(Mockery::on(static function ($arg) use ($project) {
                return $arg->is($project);
            }), Mockery::any())
            ->andThrow(
                new UnprocessableException(
                    "Invalid parameters provided.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        $this->app->instance(JiraProjectService::class, $mockService);
        $updateData = [
            'name' => 'Updated Project Name',
            'jira_project_key' => 'UPDKEY',
            'jira_project_category_id' => $project->jira_project_category_id
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$project->id", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'Invalid parameters provided.',
            'errors' => [
                'params' => 'Invalid parameters provided.'
            ]
        ]);
    }
}
