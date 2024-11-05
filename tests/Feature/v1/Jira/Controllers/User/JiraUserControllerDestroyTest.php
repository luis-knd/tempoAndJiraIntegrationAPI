<?php

namespace Tests\Feature\v1\Jira\Controllers\User;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraTeam;
use App\Models\v1\Jira\JiraUser;
use App\Services\v1\Jira\JiraUserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraUserControllerDestroyTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/users';

    #[Test]
    public function an_unauthenticated_user_cannot_delete_a_user(): void // phpcs:ignore
    {
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_delete_a_user(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        $jiraUser->jiraTeams()->attach($jiraTeam);

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'status' => Response::HTTP_OK,
            'message' => 'JiraUser deleted successfully.',
            'errors' => []
        ]);
        $jiraUser->fresh();
        $this->assertSoftDeleted('jira_users', ['id' => $jiraUser->id]);
        $this->assertSoftDeleted('jira_team_jira_user', ['jira_user_id' => $jiraUser->id]);
        $this->assertDatabaseHas('jira_teams', ['id' => $jiraTeam->id]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_delete_a_user(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('delete', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_unprocessable_exception_during_delete(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        $mockService = Mockery::mock(JiraUserService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('delete')
            ->with(Mockery::type(JiraUser::class))
            ->andThrow(
                new UnprocessableException(
                    "Unable to delete the specified user.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        $this->app->instance(JiraUserService::class, $mockService);

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'Unable to delete the specified user.',
            'errors' => [
                'params' => 'Unable to delete the specified user.'
            ]
        ]);
    }
}
