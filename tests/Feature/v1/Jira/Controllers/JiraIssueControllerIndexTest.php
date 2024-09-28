<?php

namespace Feature\v1\Jira\Controllers;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Jira\JiraProject;
use App\Models\v1\Jira\JiraProjectCategory;
use Database\Seeders\v1\Jira\JiraIssueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraIssueControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_get_issues(): void // phpcs:ignore
    {
        $response = $this->getJson($this->apiBaseUrl . '/jira/issues');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_issues(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->seed(JiraIssueSeeder::class);

        $response = $this->getJson("$this->apiBaseUrl/jira/issues");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(
            [
                'data' => [
                    'jira_issues' => [
                        [
                            'jira_issue_id',
                            'jira_issue_key',
                            'project' => [
                                'jira_project_id'
                            ],
                            'summary',
                            'development_category',
                            'status'
                        ],
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
            ]
        );
        $response->assertJsonPath('data.total', 85);
        $response->assertJsonPath('data.count', 30);
        $response->assertJsonPath('data.per_page', 30);
        $response->assertJsonPath('data.current_page', 1);
        $response->assertJsonPath('data.total_pages', 3);
    }

    #[Test]
    public function an_authenticated_user_can_get_issues_with_the_project_relation(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraIssue = JiraIssue::factory()->count(1)->create([
            'jira_issue_id' => 987654,
            'jira_issue_key' => 'LCD-123',
            'summary' => 'This is a summary about my issue',
            'development_category' => 'Migración tecnológica',
            'status' => 'Awaiting development'
        ])->first();
        $jiraProjectCategory = JiraProjectCategory::first();
        $jiraProject = JiraProject::first();

        $response = $this->getJson("$this->apiBaseUrl/jira/issues?relations=jira_projects");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(
            [
                'data' => [
                    'jira_issues' => [
                        [
                            'jira_issue_id',
                            'jira_issue_key',
                            'project' => [
                                'jira_project_id',
                                'name',
                                'category' => [
                                    'jira_category_id',
                                    'name',
                                    'description'
                                ],
                            ],
                            'summary',
                            'development_category',
                            'status'
                        ],
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
            ]
        );
        $response->assertJsonFragment([
            'data' => [
                'jira_issues' => [
                    [
                        'jira_issue_id' => 987654,
                        'jira_issue_key' => 'LCD-123',
                        'project' => [
                            'jira_project_id' => $jiraProject->jira_project_id,
                            'name' => $jiraProject->name,
                            'category' => [
                                'jira_category_id' => $jiraProjectCategory->jira_category_id,
                                'name' => $jiraProjectCategory->name,
                                'description' => $jiraProjectCategory->description
                            ],
                        ],
                        'summary' => 'This is a summary about my issue',
                        'development_category' => 'Migración tecnológica',
                        'status' => 'Awaiting development',
                        'created_at' => $jiraIssue->created_at,
                        'updated_at' => $jiraIssue->updated_at
                    ],
                ],
                'total' => 1,
                'count' => 1,
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
    public function an_authenticated_user_gets_an_empty_list_when_there_are_no_issues(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $response = $this->getJson("$this->apiBaseUrl/jira/issues");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                "jira_issues" => [],
                'total' => 0,
                'count' => 0,
                'per_page' => 30,
                'current_page' => 1,
                'total_pages' => 1
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK', 'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_issues_with_an_invalid_project_relation(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/jira/issues?relations=invalid_relation");
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
    public function it_handles_invalid_pagination_parameters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/jira/issues?page=-1&page_size=1000");

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
    public function it_can_sort_issues_by_created_at(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraIssue::factory()->count(3)->create();

        $response = $this->getJson("$this->apiBaseUrl/jira/issues?sort_by=created_at&sort_order=asc");

        $response->assertStatus(Response::HTTP_OK);
        $responseData = $response->json('data.jira_issues');

        $this->assertTrue($responseData[0]['created_at'] <= $responseData[1]['created_at']);
    }

    #[Test]
    public function it_can_filter_issues_by_status(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraIssue::factory()->create(['status' => 'open']);
        JiraIssue::factory()->create(['status' => 'closed']);

        $response = $this->getJson("$this->apiBaseUrl/jira/issues?status=open");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'status' => 'open',
        ]);
        $this->assertCount(1, $response->json('data.jira_issues'));
    }

    #[Test]
    public function it_returns_an_empty_page_if_pagination_exceeds_the_total_number_of_issues(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraIssue::factory()->count(25)->create();

        $response = $this->getJson("$this->apiBaseUrl/jira/issues?page=2");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_issues' => [],
                'total' => 25,
                'count' => 0,
                'per_page' => 30,
                'current_page' => 2,
                'total_pages' => 1
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }
}
