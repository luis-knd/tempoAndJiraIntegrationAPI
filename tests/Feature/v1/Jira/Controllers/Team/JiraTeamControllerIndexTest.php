<?php

namespace Tests\Feature\v1\Jira\Controllers\Team;

use App\Models\v1\Jira\JiraTeam;
use App\Models\v1\Jira\JiraUser;
use Database\Seeders\v1\Jira\JiraTeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\Test;

class JiraTeamControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_get_teams(): void // phpcs:ignore
    {
        $response = $this->getJson("$this->apiBaseUrl/jira/teams");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_get_teams(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->seed(JiraTeamSeeder::class);

        $response = $this->getJson("$this->apiBaseUrl/jira/teams");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'jira_teams' => [
                    [
                        'jira_team_id',
                        'name',
                    ]
                ],
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages'
            ],
            'status',
            'message',
            'errors'
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_get_teams_with_users_related(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create(['email' => 'lcandelario@lcandesign.com']);
        /** @var JiraUser $jiraUserWithoutTeams */
        $jiraUserWithoutTeams = JiraUser::factory()->create(['email' => 'withoutteams@lcandesign.com']);
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->create();
        $jiraUser->jiraTeams()->attach($jiraTeam);

        $this->seed(JiraTeamSeeder::class);

        $response = $this->getJson("$this->apiBaseUrl/jira/teams?relations=jira_users");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'jira_teams' => [
                    [
                        'jira_team_id',
                        'name',
                        'jira_users' => [
                            ['name', 'email']
                        ]
                    ]
                ],
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages'
            ],
            'status',
            'message',
            'errors'
        ]);
        $this->assertSame($response->json('data.jira_teams')[0]['jira_users'][0]['name'], $jiraUser->name);
        $this->assertDatabaseMissing('jira_team_jira_user', ['jira_user_id' => $jiraUserWithoutTeams->id]);
        $this->assertDatabaseCount('jira_team_jira_user', 1);
        $this->assertDatabaseHas('jira_team_jira_user', ['jira_user_id' => $jiraUser->id]);
    }

    #[Test]
    public function an_authenticated_user_gets_an_empty_list_when_there_are_no_teams(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/jira/teams");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_teams' => [],
                'total' => 0,
                'count' => 0,
                'per_page' => 30,
                'current_page' => 1,
                'total_pages' => 1
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_sort_teams_by_name(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraTeam::factory()->count(3)->create();

        $response = $this->getJson("$this->apiBaseUrl/jira/teams?sort=+name");

        $response->assertStatus(Response::HTTP_OK);
        $responseData = $response->json('data.jira_teams');

        $this->assertTrue($responseData[0]['name'] <= $responseData[1]['name']);
    }

    #[Test]
    public function it_handles_invalid_pagination_parameters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/jira/teams?page=-1&page_size=1000");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The page size field must not be greater than 100. (and 1 more error)',
            'errors' => [
                'page' => ['The page field must be at least 1.'],
                'page_size' => ['The page size field must not be greater than 100.']
            ]
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_teams_with_an_invalid_relation(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/jira/teams?relations=invalid_relation");

        $response->assertJsonFragment([
            'data' => [],
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'The invalid_relation is present in relations param but is not available hydration',
            'errors' => [
                'relations' => ['The invalid_relation is present in relations param but is not available hydration']
            ]
        ]);
    }

    #[Test]
    public function it_can_filter_teams_by_name(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraTeam::factory()->create(['name' => 'Alpha Team']);
        JiraTeam::factory()->create(['name' => 'Beta Team']);

        $response = $this->getJson("$this->apiBaseUrl/jira/teams?name=Alpha Team");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['name' => 'Alpha Team']);
        $this->assertCount(1, $response->json('data.jira_teams'));
    }
}
