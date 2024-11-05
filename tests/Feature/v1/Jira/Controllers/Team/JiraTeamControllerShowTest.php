<?php

namespace Tests\Feature\v1\Jira\Controllers\Team;

use App\Models\v1\Jira\JiraTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

class JiraTeamControllerShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_get_a_jira_team(): void // phpcs:ignore
    {
        // Given

        // When
        $response = $this->getJson("$this->apiBaseUrl/jira/teams/1");

        // Then
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_get_a_jira_team(): void // phpcs:ignore
    {
        // Given
        $this->loginWithFakeUser();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();

        // When
        $response = $this->getJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");

        // Then
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_team' => [
                    'jira_team_id' => $jiraTeam->jira_team_id,
                    'name' => $jiraTeam->name,
                    'created_at' => $jiraTeam->created_at,
                    'updated_at' => $jiraTeam->updated_at
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function invalid_parameters_in_request_should_return_unprocessable_entity(): void // phpcs:ignore
    {
        // Given
        $this->loginWithFakeUser();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        $invalidRelation = 'invalid_relation';

        // When
        $response = $this->getJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id?relations=$invalidRelation");

        // Then
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => "The $invalidRelation is present in relations param but is not available hydration",
            'errors' => [
                'relations' => ["The $invalidRelation is present in relations param but is not available hydration"],
            ]
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_a_non_existent_jira_team(): void // phpcs:ignore
    {
        // Given
        $this->loginWithFakeUser();
        $nonExistentTeamId = 'invalid-uuid';

        // When
        $response = $this->getJson("$this->apiBaseUrl/jira/teams/$nonExistentTeamId");

        // Then
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => "Resource $nonExistentTeamId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_get_a_jira_team_with_valid_relations(): void // phpcs:ignore
    {
        // Given
        $this->loginWithFakeUser();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        $jiraTeam->load('jiraUsers');

        // When
        $response = $this->getJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id?relations=jira_users");

        // Then
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_team' => [
                    'jira_team_id' => $jiraTeam->jira_team_id,
                    'name' => $jiraTeam->name,
                    'jira_users' => [],
                    'created_at' => $jiraTeam->created_at,
                    'updated_at' => $jiraTeam->updated_at
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_should_return_unprocessable_entity_when_authorization_exception_is_thrown(): void // phpcs:ignore
    {
        // Given
        $this->loginWithFakeUser();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('view', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        // When
        $response = $this->getJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id");

        // Then
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }
}
