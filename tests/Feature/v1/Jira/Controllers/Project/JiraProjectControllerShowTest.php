<?php

namespace Tests\Feature\v1\Jira\Controllers\Project;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraProject;
use App\Services\v1\Jira\JiraProjectService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectControllerShowTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/projects';

    #[Test]
    public function an_unauthenticated_user_cannot_get_a_project(): void // phpcs:ignore
    {
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/1");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_a_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProject::factory()->count(4)->create();
        $jiraProject = JiraProject::first();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProject->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project' => [
                    'jira_project_id' => $jiraProject->jira_project_id,
                    'name' => $jiraProject->name,
                    'jira_project_key' => $jiraProject->jira_project_key,
                    'category' => [
                        'jira_category_id' => $jiraProject->jira_project_category_id
                    ]
                ]
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_an_invalid_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $id = Str::uuid()->toString();
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$id");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => "Resource $id not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_get_a_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $jiraProject */
        $jiraProject = JiraProject::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('view', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProject->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function multiple_users_can_access_the_same_project_simultaneously(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $jiraProject */
        $jiraProject = JiraProject::factory()->create();

        $responses = collect(range(1, 10))->map(function () use ($jiraProject) {
            return $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProject->id");
        });

        $responses->each(function ($response) {
            $response->assertStatus(Response::HTTP_OK);
        });
    }

    #[Test]
    public function an_authenticated_user_can_request_specific_fields(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $jiraProject */
        $jiraProject = JiraProject::factory()->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProject->id?fields=name,jira_project_key");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'jira_project' => [
                    'name',
                    'jira_project_key'
                ]
            ]
        ]);
        $response->assertJsonMissing(['jira_project_id']);
    }

    #[Test]
    public function it_handles_unprocessable_exception_correctly(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $jiraProject */
        $jiraProject = JiraProject::factory()->create();
        $mockService = Mockery::mock(JiraProjectService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('load')
            ->with(Mockery::on(static function ($arg) use ($jiraProject) {
                return $arg->is($jiraProject);
            }), Mockery::any())
            ->andThrow(
                new UnprocessableException(
                    "Invalid parameters provided.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        $this->app->instance(JiraProjectService::class, $mockService);

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProject->id?invalid_param=value");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'Invalid parameters provided.',
            'errors' => [
                'params' => 'Invalid parameters provided.'
            ]
        ]);
    }
}
