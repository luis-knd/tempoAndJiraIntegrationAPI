<?php

namespace Tests\Feature\v1\Jira\Controllers\Issue;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraIssue;
use App\Services\v1\Jira\JiraIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraIssueControllerShowTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = "jira/issues";

    #[Test]
    public function an_unauthenticated_user_cannot_get_an_issue(): void // phpcs:ignore
    {
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/1");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_an_issue(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraIssue::factory()->count(4)->create();
        $jiraIssue = JiraIssue::first();
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraIssue->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_issue' => [
                    'jira_issue_id' => $jiraIssue->jira_issue_id,
                    'jira_issue_key' => $jiraIssue->jira_issue_key,
                    'project' => [
                        'jira_project_id' => $jiraIssue->jira_project_id,
                    ],
                    'summary' => $jiraIssue->summary,
                    'development_category' => $jiraIssue->development_category,
                    'status' => $jiraIssue->status,
                    'created_at' => $jiraIssue->created_at,
                    'updated_at' => $jiraIssue->updated_at
                ]
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function invalid_parameters_in_request_should_return_unprocessable_entity(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraIssue $jiraIssue */
        $jiraIssue = JiraIssue::factory()->create();
        $nonExistentModel = 'invalid_model_relation';

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraIssue->id?relations=$nonExistentModel");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => "The $nonExistentModel is present in relations param but is not available hydration",
            'errors' => [
                'relations' => ["The $nonExistentModel is present in relations param but is not available hydration"],
            ]
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_a_non_existent_issue(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $nonExistentIssueId = 9999;

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$nonExistentIssueId");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => "Resource $nonExistentIssueId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_get_an_issue_with_valid_relations(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraIssue $jiraIssue */
        $jiraIssue = JiraIssue::factory()->create();
        $jiraIssue->load('jiraProjects');

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraIssue->id?relations=jira_projects");

        $response->assertStatus(Response::HTTP_OK);
        $jiraIssue = $jiraIssue->toArray();
        $response->assertJsonFragment([
            'data' => [
                'jira_issue' => [
                    'jira_issue_id' => $jiraIssue['jira_issue_id'],
                    'jira_issue_key' => $jiraIssue['jira_issue_key'],
                    'project' => [
                        'jira_project_id' => $jiraIssue['jira_project_id'],
                        'jira_project_key' => $jiraIssue['jira_projects']['jira_project_key'],
                        'category' => [
                            'description' => $jiraIssue['jira_projects']['jira_project_category']['description'],
                            'name' => $jiraIssue['jira_projects']['jira_project_category']['name'],
                            'jira_category_id' =>
                                $jiraIssue['jira_projects']['jira_project_category']['jira_category_id']
                        ],
                        'name' => $jiraIssue['jira_projects']['name']
                    ],
                    'development_category' => $jiraIssue['development_category'],
                    'status' => $jiraIssue['status'],
                    'summary' => $jiraIssue['summary'],
                    'created_at' => $jiraIssue['created_at'],
                    'updated_at' => $jiraIssue['updated_at']
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function multiple_users_can_access_the_same_issue_simultaneously(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraIssue $jiraIssue */
        $jiraIssue = JiraIssue::factory()->create();

        $responses = collect(range(1, 10))->map(function () use ($jiraIssue) {
            return $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraIssue->id");
        });

        $responses->each(function ($response) {
            $response->assertStatus(Response::HTTP_OK);
        });
    }

    #[Test]
    public function it_should_return_unprocessable_entity_when_unprocessable_exception_is_thrown(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraIssue $jiraIssue */
        $jiraIssue = JiraIssue::factory()->create();

        $this->mock(JiraIssueService::class, function ($mock) {
            $mock->shouldReceive('load')
                ->andThrow(new UnprocessableException(
                    'Invalid parameters',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                ));
        });

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraIssue->id");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Invalid parameters',
            'errors' => [
                'params' => 'Invalid parameters'
            ]
        ]);
    }
}
