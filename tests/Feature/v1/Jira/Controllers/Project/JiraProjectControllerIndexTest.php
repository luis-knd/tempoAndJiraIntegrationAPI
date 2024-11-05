<?php

namespace Tests\Feature\v1\Jira\Controllers\Project;

use App\Exceptions\UnprocessableException;
use App\Http\Requests\v1\Jira\JiraProjectRequest;
use App\Models\v1\Jira\JiraProject;
use App\Services\v1\Jira\JiraProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/projects';

    public function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function an_unauthenticated_user_cannot_get_projects(): void // phpcs:ignore
    {
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_projects(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProject::factory()->count(10)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(
            [
                'data' => [
                    'jira_projects' => [
                        [
                            'jira_project_id',
                            'name',
                            'category' => [
                                'jira_category_id',
                            ],
                            'jira_project_key'
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
            ]
        );
    }

    #[Test]
    public function an_authenticated_user_can_get_an_empty_list_when_there_are_no_projects(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_projects' => [],
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
    public function an_authenticated_user_cannot_get_projects_with_invalid_parameters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProject::factory()->count(2)->create();
        $invalidSortParam = "invalidSortParam";

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?sort=$invalidSortParam");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => "The $invalidSortParam is present in sort param but is not available for sort",
            'errors' => [
                'sort' => ["The $invalidSortParam is present in sort param but is not available for sort"]
            ]
        ]);
    }

    #[Test]
    public function it_handles_invalid_pagination_parameters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?page=-1&page_size=1000");

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
    public function it_returns_an_empty_page_if_pagination_exceeds_the_total_number_of_projects(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProject::factory()->count(25)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?page=2");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_projects' => [],
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
    public function it_should_return_unprocessable_entity_when_json_exception_is_thrown(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->mock(JiraProjectService::class, function (MockInterface $mock) {
            // @phpstan-ignore-next-line
            $mock->shouldReceive('index')
                ->andThrow(new JsonException('Unable to process JSON data'));
        });

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [],
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Unable to process JSON data',
            'errors' => ['error' => 'Unable to process JSON data']
        ]);
    }

    #[Test]
    public function it_should_return_bad_request_when_unprocessable_exception_is_thrown(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->mock(JiraProjectRequest::class, function (MockInterface $mock) {
            $mock->shouldReceive('validated')->andReturn([]);
        });

        $this->mock(JiraProjectService::class, function (MockInterface $mock) {
            // @phpstan-ignore-next-line
            $mock->shouldReceive('index')
                ->andThrow(new UnprocessableException('Invalid parameters', Response::HTTP_UNPROCESSABLE_ENTITY));
        });

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [],
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Invalid parameters',
            'errors' => ['params' => 'Invalid parameters']
        ]);
    }

    #[Test]
    public function it_returns_a_list_of_projects_with_a_name_filter_with_in_operator(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $projects = [
            ['name' => 'Solido', 'jira_project_key' => 'SOLIDO'],
            ['name' => 'Liquido', 'jira_project_key' => 'LIQUIDO'],
            ['name' => 'Gaseoso', 'jira_project_key' => 'GASEOSO']
        ];
        foreach ($projects as $project) {
            JiraProject::factory()->create($project);
        }
        $projectsFromDb = JiraProject::all();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?name[in]=Solido,Liquido");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                "jira_projects" => [
                    [
                        "jira_project_id" => $projectsFromDb[0]->jira_project_id,
                        "name" => 'Solido',
                        "category" => [
                            "jira_category_id" => $projectsFromDb[0]->jira_project_category_id,
                        ],
                        "jira_project_key" => 'SOLIDO'
                    ],
                    [
                        "jira_project_id" => $projectsFromDb[1]->jira_project_id,
                        "name" => 'Liquido',
                        "category" => [
                            "jira_category_id" =>  $projectsFromDb[1]->jira_project_category_id,
                        ],
                        "jira_project_key" => 'LIQUIDO'
                    ]
                ],
                'total' => 2,
                'count' => 2,
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
    public function it_returns_a_list_of_projects_with_a_name_filter_with_not_in_operator(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $projects = [
            ['name' => 'Solido', 'jira_project_key' => 'SOLIDO'],
            ['name' => 'Liquido', 'jira_project_key' => 'LIQUIDO'],
            ['name' => 'Gaseoso', 'jira_project_key' => 'GASEOSO']
        ];
        foreach ($projects as $project) {
            JiraProject::factory()->create($project);
        }
        $projectsFromDb = JiraProject::all();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?name[nin]=Solido,Liquido");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                "jira_projects" => [
                    [
                        "jira_project_id" => $projectsFromDb[2]->jira_project_id,
                        "name" => 'Gaseoso',
                        "category" => [
                            "jira_category_id" => $projectsFromDb[2]->jira_project_category_id,
                        ],
                        "jira_project_key" => 'GASEOSO'
                    ]
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
