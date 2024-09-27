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
        JiraIssue::factory()->count(1)->create([
            'jira_issue_id' => 987654,
            'jira_issue_key' => 'LCD-123',
            'summary' => 'This is a summary about my issue',
            'development_category' => 'Migración tecnológica',
            'status' => 'Awaiting development'
        ]);
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
                        'status' => 'Awaiting development'
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
}
