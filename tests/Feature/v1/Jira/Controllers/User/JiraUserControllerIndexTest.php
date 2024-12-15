<?php

namespace Tests\Feature\v1\Jira\Controllers\User;

use App\Exceptions\BadRequestException;
use App\Http\Requests\v1\Auth\v1\Jira\JiraUserRequest;
use App\Models\v1\Jira\JiraTeam;
use App\Models\v1\Jira\JiraUser;
use App\Services\v1\Jira\JiraUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraUserControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/users';

    #[Test]
    public function an_unauthenticated_user_cannot_get_users(): void // phpcs:ignore
    {
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_users(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraUser::factory()->count(10)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(
            [
                'data' => [
                    'jira_users' => [
                        [
                            'jira_user_id',
                            'name',
                            'email',
                            'jira_user_type',
                            'active'
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
    public function an_authenticated_user_can_get_an_empty_list_when_there_are_no_users(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_users' => [],
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
    public function an_authenticated_user_cannot_get_users_with_invalid_parameters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraUser::factory()->count(2)->create();
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
    public function it_returns_an_empty_page_if_pagination_exceeds_the_total_number_of_users(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraUser::factory()->count(25)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?page=2");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_users' => [],
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
    public function it_should_return_bad_request_when_exception_is_thrown(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->mock(JiraUserRequest::class, function (MockInterface $mock) {
            $mock->shouldReceive('validated')->andReturn([]);
        });

        $this->mock(JiraUserService::class, function (MockInterface $mock) {
            // @phpstan-ignore-next-line
            $mock->shouldReceive('index')
                ->andThrow(new BadRequestException('Invalid parameters', Response::HTTP_BAD_REQUEST));
        });

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson([
            'data' => [],
            'status' => Response::HTTP_BAD_REQUEST,
            'message' => 'Invalid parameters',
            'errors' => ['error' => 'Invalid parameters']
        ]);
    }

    #[Test]
    public function it_should_return_unprocessable_entity_when_json_exception_is_thrown(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->mock(JiraUserService::class, function (MockInterface $mock) {
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
    public function an_authenticated_user_can_get_users_with_teams(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        /** @var JiraTeam $jiraTeam */
        $jiraTeam = JiraTeam::factory()->count(2)->create();
        $jiraUser->jiraTeams()->attach($jiraTeam);

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?relations=jira_teams");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'jira_users' => [
                    [
                        'jira_user_id',
                        'name',
                        'email',
                        'jira_user_type',
                        'active',
                        'jira_teams' => [
                            [
                                'jira_team_id',
                                'name',
                            ]
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
    }
}
