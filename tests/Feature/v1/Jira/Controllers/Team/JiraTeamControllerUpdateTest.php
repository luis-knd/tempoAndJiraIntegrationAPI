<?php

namespace Tests\Feature\v1\Jira\Controllers\Team;

use App\Models\v1\Jira\JiraTeam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraTeamControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_update_a_jira_team(): void // phpcs:ignore
    {
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();

        $payload = [
            'name' => 'Updated Team Name'
        ];

        $response = $this->putJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id", $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_update_a_jira_team(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        Gate::shouldReceive('authorize')->with('update', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $payload = [
            'name' => 'Unauthorized Update'
        ];

        $response = $this->putJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id", $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_update_a_jira_team_with_valid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();

        $payload = [
            'name' => 'Updated Team Name'
        ];

        $response = $this->putJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(function (AssertableJson $json) use ($jiraTeam) {
            $json->where('status', Response::HTTP_OK)
                ->where('message', 'OK')
                ->where('errors', [])
                ->has('data.jira_team', function (AssertableJson $json) use ($jiraTeam) {
                    $json->where('jira_team_id', $jiraTeam->jira_team_id);
                    $json->where('name', 'Updated Team Name')
                        ->etc();
                });
        });

        $this->assertDatabaseHas('jira_teams', [
            'id' => $jiraTeam->id,
            'name' => 'Updated Team Name'
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_a_jira_team_with_invalid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();

        $payload = [
            'name' => ''
        ];

        $response = $this->putJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The name field is required.',
            'errors' => [
                'name' => ['The name field is required.']
            ]
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_a_non_existent_jira_team(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $nonExistentTeamId = 'non-existent-id';

        $payload = [
            'name' => 'Updated Team Name'
        ];

        $response = $this->putJson("$this->apiBaseUrl/jira/teams/$nonExistentTeamId", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => "Resource $nonExistentTeamId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_partially_update_a_jira_team(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create([
            'name' => 'Original Team Name'
        ]);

        $payload = [
            'name' => 'Partially Updated Team Name'
        ];

        $response = $this->putJson("$this->apiBaseUrl/jira/teams/$jiraTeam->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(function (AssertableJson $json) use ($jiraTeam) {
            $json->where('status', Response::HTTP_OK)
                ->where('message', 'OK')
                ->where('errors', [])
                ->has('data.jira_team', function (AssertableJson $json) use ($jiraTeam) {
                    $json->where('jira_team_id', $jiraTeam->jira_team_id);
                    $json->where('name', 'Partially Updated Team Name')
                        ->etc();
                });
        });

        $this->assertDatabaseHas('jira_teams', [
            'id' => $jiraTeam->id,
            'name' => 'Partially Updated Team Name'
        ]);
    }
}
