<?php

namespace Tests\Feature\v1\Jira\Controllers\Team;

use App\Exceptions\JsonResponseAttachment;
use App\Models\v1\Jira\JiraTeam;
use App\Services\v1\Jira\JiraTeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Mockery;

class JiraTeamControllerDestroyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_delete_a_jira_team(): void // phpcs:ignore
    {
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();

        $response = $this->deleteJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_delete_a_jira_team(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('delete', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $response = $this->deleteJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_delete_a_jira_team(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();

        $response = $this->deleteJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'message' => 'JiraTeam deleted successfully.',
            'errors' => []
        ]);

        $this->assertSoftDeleted('jira_teams', [
            'id' => $jiraTeam->id,
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_delete_a_non_existent_jira_team(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $nonExistentTeamId = 'non-existent-id';

        $response = $this->deleteJson("$this->apiBaseUrl/jira/teams/$nonExistentTeamId");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => "Resource $nonExistentTeamId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_delete_a_soft_deleted_jira_team(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        $jiraTeam->delete();

        $response = $this->deleteJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => "Resource $jiraTeam->id not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_concurrent_deletion_attempts(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();

        $response1 = $this->deleteJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");
        $response2 = $this->deleteJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");

        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response2->assertJsonFragment([
            'message' => "Resource $jiraTeam->id not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_json_response_attachment_exception_during_deletion(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        $mockService = Mockery::mock(JiraTeamService::class);
        $mockService->shouldReceive('delete') //@phpstan-ignore-line
        ->once()
            ->andThrow(new JsonResponseAttachment(
                'Custom error message',
                Response::HTTP_BAD_REQUEST
            ));

        $this->app->instance(JiraTeamService::class, $mockService);

        $response = $this->deleteJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson([
            'message' => 'Custom error message',
            'errors' => []
        ]);
    }
}
