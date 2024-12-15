<?php

namespace Tests\Feature\v1\Jira\Controllers\ProjectCategory;

use App\Exceptions\BadRequestException;
use App\Http\Requests\v1\Auth\v1\Jira\JiraProjectCategoryRequest;
use App\Models\v1\Jira\JiraProjectCategory;
use App\Services\v1\Jira\JiraProjectCategoryService;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectCategoryControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    private Generator $faker;
    private string $urlPath = 'jira/project-categories';

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }

    #[Test]
    public function an_unauthenticated_user_cannot_get_categories(): void // phpcs:ignore
    {
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_categories(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->count(10)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(
            [
                'data' => [
                    'jira_project_categories' => [
                        [
                            'jira_category_id',
                            'name',
                            'description'
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
    public function an_authenticated_user_can_get_an_empty_list_when_there_are_no_categories(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_categories' => [],
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
    public function an_authenticated_user_cannot_get_categories_with_invalid_parameters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->count(2)->create();
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
    public function it_can_sort_categories_by_name_asc_and_description_desc(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $categories = [
            ['name' => 'Agua', 'description' => 'Agua Mineral'],
            ['name' => 'Agua', 'description' => 'Agua con Gas'],
            ['name' => 'Soda', 'description' => 'Soda Natural'],
            ['name' => 'Jugo', 'description' => 'Naranja'],
            ['name' => 'Jugo', 'description' => 'Naranja 70% Natural'],
            ['name' => 'Soda', 'description' => 'Soda de Sabor'],
            ['name' => 'Jugo', 'description' => 'Naranja 100% Natural'],
            ['name' => 'Jugo', 'description' => 'Limón'],
            ['name' => 'Jugo', 'description' => 'Zanahoria'],
            ['name' => 'Agua', 'description' => 'Agua Saborizada']
        ];
        $expected = [
            ['name' => 'Agua', 'description' => 'Agua con Gas'],
            ['name' => 'Agua', 'description' => 'Agua Saborizada'],
            ['name' => 'Agua', 'description' => 'Agua Mineral'],
            ['name' => 'Jugo', 'description' => 'Zanahoria'],
            ['name' => 'Jugo', 'description' => 'Naranja 70% Natural'],
            ['name' => 'Jugo', 'description' => 'Naranja 100% Natural'],
            ['name' => 'Jugo', 'description' => 'Naranja'],
            ['name' => 'Jugo', 'description' => 'Limón'],
            ['name' => 'Soda', 'description' => 'Soda de Sabor'],
            ['name' => 'Soda', 'description' => 'Soda Natural']
        ];
        foreach ($categories as $category) {
            JiraProjectCategory::factory()->create($category);
        }

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?sort=name,-description");

        $response->assertStatus(Response::HTTP_OK);
        $responseData = $response->json('data.jira_project_categories');
        $this->assertCount(count($categories), $responseData);
        foreach ($expected as $index => $expectedCategory) {
            $this->assertSame($expectedCategory['name'], $responseData[$index]['name']);
            $this->assertSame($expectedCategory['description'], $responseData[$index]['description']);
        }
    }

    #[Test]
    public function it_returns_an_empty_page_if_pagination_exceeds_the_total_number_of_categories(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->count(25)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?page=2");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_categories' => [],
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
    public function it_returns_an_empty_list_if_there_are_no_project_categories_different_from_the_given_one(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $categories = [
            ['name' => 'Agua', 'description' => 'Agua con Gas'],
            ['name' => 'Agua', 'description' => 'Agua Saborizada'],
            ['name' => 'Agua', 'description' => 'Agua Mineral']
        ];
        foreach ($categories as $category) {
            JiraProjectCategory::factory()->create($category);
        }

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?name[ne]=Agua");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                "jira_project_categories" => [],
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
    public function it_can_paginate_results_with_custom_page_size(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->count(15)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?page=2&page_size=5");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'total' => 15,
            'count' => 5,
            'per_page' => 5,
            'current_page' => 2,
            'total_pages' => 3
        ]);
        $this->assertCount(5, $response->json('data.jira_project_categories'));
    }

    #[Test]
    public function it_cannot_filter_categories_by_description_using_wrong_criteria_parameter(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->create(['description' => 'Test Description']);
        JiraProjectCategory::factory()->count(5)->create();
        $wrongParam = 'like';

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?description[$wrongParam]=Test");

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
    public function it_can_filter_categories_by_description_using_like_at_the_end(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->create(['description' => 'Test Description']);
        JiraProjectCategory::factory()->count(5)->create(['description' => $this->faker->dayOfWeek]);

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?description[lk]=Test%25");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'total' => 1,
            'count' => 1
        ]);
        $this->assertCount(1, $response->json('data.jira_project_categories'));
        $this->assertEquals('Test Description', $response->json('data.jira_project_categories.0.description'));
    }

    #[Test]
    public function it_can_filter_categories_by_description_using_like_at_the_beginning(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->create(['description' => 'Test Description']);
        JiraProjectCategory::factory()->count(5)->create(['description' => $this->faker->dayOfWeek]);

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?description[lk]=%25Description");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'total' => 1,
            'count' => 1
        ]);
        $this->assertCount(1, $response->json('data.jira_project_categories'));
        $this->assertEquals('Test Description', $response->json('data.jira_project_categories.0.description'));
    }

    #[Test]
    public function it_can_apply_multiple_filters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->create(['name' => 'Test A', 'description' => 'Description 1']);
        JiraProjectCategory::factory()->create(['name' => 'Test B', 'description' => 'Description 2']);
        JiraProjectCategory::factory()->create(['name' => 'Other', 'description' => 'Description 3']);

        $response = $this->getJson(
            "$this->apiBaseUrl/$this->urlPath?name[lk]=Test%25&description=Description 1"
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'total' => 1,
            'count' => 1
        ]);
        $this->assertCount(1, $response->json('data.jira_project_categories'));
        $this->assertEquals('Test A', $response->json('data.jira_project_categories.0.name'));
    }

    #[Test]
    public function it_should_return_bad_request_when_exception_is_thrown(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->mock(JiraProjectCategoryRequest::class, function (MockInterface $mock) {
            $mock->shouldReceive('validated')->andReturn([]);
        });

        $this->mock(JiraProjectCategoryService::class, function (MockInterface $mock) {
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
        $this->mock(JiraProjectCategoryService::class, function (MockInterface $mock) {
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
}
