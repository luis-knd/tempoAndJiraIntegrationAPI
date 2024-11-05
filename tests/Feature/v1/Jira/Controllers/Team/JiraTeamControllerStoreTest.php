<?php

namespace Tests\Feature\v1\Jira\Controllers\Team;

use App\Models\v1\Jira\JiraTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class JiraTeamControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_create_a_jira_team(): void // phpcs:ignore
    {
        // Given:

        // When:
        $response = $this->postJson("$this->apiBaseUrl/jira/teams");

        // Then:
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_create_a_jira_team_with_valid_data(): void // phpcs:ignore
    {
        // Given:
        $this->loginWithFakeUser();
        $jiraTeamId = Str::Uuid();
        $payload = [
            'jira_team_id' => $jiraTeamId,
            'name' => 'Development Team',
        ];

        // When:
        $response = $this->postJson("$this->apiBaseUrl/jira/teams", $payload);

        // Then:
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_team' => [
                    'jira_team_id' => $jiraTeamId,
                    'name' => 'Development Team',
                    'created_at' => $response->json('data.jira_team.created_at'),
                    'updated_at' => $response->json('data.jira_team.updated_at'),
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_create_a_jira_team_with_invalid_data(): void // phpcs:ignore
    {
        // Given:
        $this->loginWithFakeUser();
        $payload = [
            'jira_team_id' => '',
            'name' => '',
        ];

        // When:
        $response = $this->postJson("$this->apiBaseUrl/jira/teams", $payload);

        // Then:
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The jira team id field is required. (and 1 more error)',
            'errors' => [
                'jira_team_id' => ['The jira team id field is required.'],
                'name' => ['The name field is required.'],
            ]
        ]);
    }

    #[Test]
    public function it_prevents_creating_a_duplicate_jira_team(): void // phpcs:ignore
    {
        // Given:
        $this->loginWithFakeUser();
        $jiraTeamId = Str::Uuid();
        JiraTeam::factory()->create(['jira_team_id' => $jiraTeamId]);

        $payload = [
            'jira_team_id' => $jiraTeamId,
            'name' => 'Duplicate Team',
        ];

        // When:
        $response = $this->postJson("$this->apiBaseUrl/jira/teams", $payload);

        // Then:
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The jira team id has already been taken.',
            'errors' => [
                'jira_team_id' => ['The jira team id has already been taken.']
            ]
        ]);
    }

    #[Test]
    public function it_sanitizes_html_input_for_jira_team_creation(): void // phpcs:ignore
    {
        // Given: an authenticated user and malicious HTML input
        $this->loginWithFakeUser();
        $fakeJiraTeamId = Str::Uuid();
        $payload = [
            'jira_team_id' => '<script>alert(\"XSS\")</script>' . $fakeJiraTeamId,
            'name' => '<b>Team Name</b><script>alert(\"XSS\")</script>',
        ];

        // When:
        $response = $this->postJson("$this->apiBaseUrl/jira/teams", $payload);

        // Then:
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_team' => [
                    'jira_team_id' => $fakeJiraTeamId,
                    'name' => 'Team Name',
                    'created_at' => $response->json('data.jira_team.created_at'),
                    'updated_at' => $response->json('data.jira_team.updated_at'),
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }
}
