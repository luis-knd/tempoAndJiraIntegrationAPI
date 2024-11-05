<?php

namespace Tests\Feature\v1\Jira\Controllers\Issue;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Jira\JiraProject;
use App\Models\v1\Jira\JiraProjectCategory;
use Database\Seeders\v1\Jira\JiraIssuesSeeder;
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
        $this->seed(JiraIssuesSeeder::class);

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
        /** @var JiraIssue $jiraIssue */
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
                            'jira_project_key' => $jiraProject->jira_project_key,
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
    public function it_can_filter_issues_by_jira_issue_id_using_criteria_less_than(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $issuesIds = [
            ['jira_issue_id' => 123],
            ['jira_issue_id' => 500],
            ['jira_issue_id' => 690],
            ['jira_issue_id' => 830],
            ['jira_issue_id' => 12],
            ['jira_issue_id' => 72]
        ];
        foreach ($issuesIds as $issue) {
            JiraIssue::factory()->create($issue);
        }

        $response = $this->getJson("$this->apiBaseUrl/jira/issues?jira_issue_id[lt]=500&sort=-jira_issue_id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'total' => 3,
            'count' => 3
        ]);
        $this->assertCount(3, $response->json('data.jira_issues'));
        $this->assertEquals('123', $response->json('data.jira_issues.0.jira_issue_id'));
        $this->assertEquals('12', $response->json('data.jira_issues.2.jira_issue_id'));
    }

    #[Test]
    public function it_cannot_filter_issues_by_status_using_wrong_criteria_parameter(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraIssue::factory()->create(['status' => 'Awaiting Development']);
        JiraIssue::factory()->create(['status' => 'Awaiting Test']);
        JiraIssue::factory()->create(['status' => 'Closed']);
        $wrongParam = 'like';

        $response = $this->getJson("$this->apiBaseUrl/jira/issues?status[$wrongParam]=Awaiting%25");

        $response->assertJsonFragment([
            'data' => [],
            'message' => "$wrongParam is not an acceptable query criteria",
            'errors' => [
                'params' => "$wrongParam is not an acceptable query criteria",
            ],
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY
        ]);
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

    #[Test]
    public function an_authenticated_user_can_get_issues_with_the_project_relation_an_filter_by_project_key(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProject $jiraProject */
        $jiraProject = JiraProject::factory()->create([
            'jira_project_id' => 123456,
            'name' => 'project name test',
            'jira_project_key' => 'LCD',
        ])->first();
        /** @var JiraProjectCategory $jiraProjectCategory */
        $jiraProjectCategory = JiraProjectCategory::where(
            'jira_category_id',
            $jiraProject->jira_project_category_id
        )->first();
        JiraIssue::factory()->count(5)->create();
        /** @var JiraIssue $jiraIssue */
        $jiraIssue = JiraIssue::factory()->count(1)->create([
            'jira_project_id' => $jiraProject->jira_project_id,
            'summary' => 'This is a summary about my issue',
            'development_category' => 'Migración tecnológica',
            'status' => 'Awaiting development',
        ])->first();

        $response = $this->getJson(
            "$this->apiBaseUrl/jira/issues?relations=jira_projects&jira_projects_jira_project_key=LCD"
        );

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(1, $response->json('data.jira_issues'));
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
                        'jira_issue_id' => $jiraIssue->jira_issue_id,
                        'jira_issue_key' => $jiraIssue->jira_issue_key,
                        'project' => [
                            'jira_project_id' => 123456,
                            'jira_project_key' => 'LCD',
                            'name' => 'project name test',
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
}
