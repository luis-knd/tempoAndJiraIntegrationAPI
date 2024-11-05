<?php

namespace Tests\Feature\v1\Jira\Controllers\ProjectCategory;

use App\Exceptions\JsonResponseAttachment;
use App\Models\v1\Jira\JiraProjectCategory;
use App\Services\v1\Jira\JiraProjectCategoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectCategoryControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/project-categories';

    #[Test]
    public function an_unauthenticated_user_cannot_update_a_project_category(): void // phpcs:ignore
    {
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        $payload = [
            'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_update_a_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('update', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $payload = [
            'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
            'name' => 'Unauthorized Update',
            'description' => 'Unauthorized Description'
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_update_a_project_category_with_valid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        $payload = [
            'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_category' => [
                    'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
                    'name' => 'Updated Name',
                    'description' => 'Updated Description',
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_a_project_category_with_invalid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        $payload = [
            'name' => '',
            'description' => ''
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

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
    public function an_authenticated_user_cannot_update_a_non_existent_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $nonExistentCategoryId = 9999;
        $payload = [
            'jira_category_id' => 'NEW-123',
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$nonExistentCategoryId", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => "Resource $nonExistentCategoryId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_the_jira_category_id(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create([
            'jira_category_id' => 'OLD-123',
        ]);

        $payload = [
            'jira_category_id' => 'NEW-456',
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_category' => [
                    'jira_category_id' => 'OLD-123',
                    'name' => 'Updated Name',
                    'description' => 'Updated Description',
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);

        $this->assertDatabaseHas('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
            'jira_category_id' => 'OLD-123',
        ]);

        $this->assertDatabaseMissing('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
            'jira_category_id' => 'NEW-456',
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_update_with_same_data_without_errors(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create([
            'name' => 'Same Name',
            'description' => 'Same Description',
        ]);

        $payload = [
            'name' => 'Same Name',
            'description' => 'Same Description',
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_category' => [
                    'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
                    'name' => 'Same Name',
                    'description' => 'Same Description',
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);

        $this->assertDatabaseHas('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
            'name' => 'Same Name',
            'description' => 'Same Description',
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_partially_update_a_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]);
        $payload = [
            'name' => 'Partially Updated Name',
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_category' => [
                    'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
                    'name' => 'Partially Updated Name',
                    'description' => 'Original Description',
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);

        $this->assertDatabaseHas('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
            'name' => 'Partially Updated Name',
            'description' => 'Original Description',
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_update_with_special_characters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        $payload = [
            'name' => 'Name with special characters !@#$%^&*()',
            'description' => 'Description with <script>alert("XSS")</script>',
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_category' => [
                    'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
                    'name' => 'Name with special characters !@#$%^&*()',
                    'description' => '<p>Description with </p>',
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);

        $this->assertDatabaseHas('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
            'name' => 'Name with special characters !@#$%^&*()',
            'description' => '<p>Description with </p>',
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_with_excessively_long_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        $longName = str_repeat('a', 256);

        $payload = [
            'name' => $longName,
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The name field must not be greater than 255 characters.',
            'errors' => [
                'name' => ['The name field must not be greater than 255 characters.'],
            ]
        ]);

        $this->assertDatabaseHas('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
            'name' => $jiraProjectCategory->name, // @phpstan-ignore-line
            'description' => $jiraProjectCategory->description, // @phpstan-ignore-line
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_with_invalid_data_types(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        $payload = [
            'name' => 67890,
            'description' => true,
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The name field must be a string. (and 1 more error)',
            'errors' => [
                'name' => ['The name field must be a string.'],
                'description' => ['The description field must be a string.'],
            ]
        ]);

        $this->assertDatabaseHas('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
            'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
            'name' => $jiraProjectCategory->name, // @phpstan-ignore-line
            'description' => $jiraProjectCategory->description, // @phpstan-ignore-line
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_a_soft_deleted_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();
        $jiraProjectCategory->delete();

        $payload = [
            'name' => 'Attempted Update Name',
            'description' => 'Attempted Update Description',
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => "Resource {$jiraProjectCategory->id} not found.", // @phpstan-ignore-line
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_update_with_null_values_if_allowed(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create([
            'description' => 'Original Description',
        ]);

        $payload = [
            'name' => 'Updated Name',
            'description' => null,
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_category' => [
                    'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
                    'name' => 'Updated Name',
                    'description' => null,
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);

        $this->assertDatabaseHas('jira_project_categories', [
            'id' => $jiraProjectCategory->id, // @phpstan-ignore-line
            'jira_category_id' => $jiraProjectCategory->jira_category_id, // @phpstan-ignore-line
            'name' => 'Updated Name',
            'description' => null,
        ]);
    }

    #[Test]
    public function it_handles_json_response_attachment_exception_during_update(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();
        $mockService = Mockery::mock(JiraProjectCategoryService::class);
        $mockService->shouldReceive('update') // @phpstan-ignore-line
            ->once()
            ->andThrow(new JsonResponseAttachment(
                'Custom error message',
                Response::HTTP_BAD_REQUEST
            ));

        $this->app->instance(JiraProjectCategoryService::class, $mockService);

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/{$jiraProjectCategory->id}", $payload);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson([
            'message' => 'Custom error message',
            'errors' => []
        ]);
    }
}
