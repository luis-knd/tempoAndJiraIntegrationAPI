<?php

namespace Tests\Feature\v1\Jira\Controllers\ProjectCategory;

use App\Models\v1\Jira\JiraProjectCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectCategoryControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/project-categories';

    #[Test]
    public function an_unauthenticated_user_cannot_store_a_category(): void // phpcs:ignore
    {
        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", [
            'name' => 'Test Category',
            'description' => 'Test Description'
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_store_a_valid_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $categoryData = [
            'name' => 'New Category',
            'description' => 'New Description',
            'jira_category_id' => 22
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $categoryData);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                "jira_project_category" => [
                    'jira_category_id',
                    'name',
                    'description'
                ]
            ],
            'status',
            'message',
            'errors'
        ]);
        $this->assertDatabaseHas('jira_project_categories', $categoryData);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_store_a_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        Gate::shouldReceive('authorize')
            ->with('create', JiraProjectCategory::class)
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');
        $categoryData = [
            'name' => 'New Category',
            'description' => 'New Description',
            'jira_category_id' => 22
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $categoryData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_returns_validation_errors_for_invalid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $invalidData = [
            'name' => '',
            'jira_category_id' => null,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $invalidData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['name', 'jira_category_id']);
    }

    #[Test]
    public function it_prevents_duplicate_category_by_names(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->create(['name' => 'Existing Category']);

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", [
            'name' => 'Existing Category',
            'description' => 'New Description',
            'jira_category_id' => 22,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_prevents_duplicate_category_by_jira_category_ids(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->create(['jira_category_id' => 22]);

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", [
            'name' => 'Existing Category',
            'description' => 'New Description',
            'jira_category_id' => 22,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['jira_category_id']);
    }

    #[Test]
    public function it_trims_whitespace_from_input_fields(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $categoryData = [
            'name' => '  Trimmed Category  ',
            'description' => '  Trimmed Description  ',
            'jira_category_id' => 22,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $categoryData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_project_categories', [
            'name' => 'Trimmed Category',
            'description' => 'Trimmed Description'
        ]);
    }

    #[Test]
    public function it_handles_minimum_required_fields(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $categoryData = [
            'name' => 'Minimal Category',
            'jira_category_id' => 22,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $categoryData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_project_categories', [
            'name' => 'Minimal Category',
            'description' => null
        ]);
    }

    #[Test]
    public function it_handles_maximum_length_inputs(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $categoryData = [
            'name' => str_repeat('a', 255),
            'jira_category_id' => 22
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $categoryData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_project_categories', $categoryData);
    }

    #[Test]
    public function it_sanitizes_html_input(): void// phpcs:ignore
    {
        $this->loginWithFakeUser();
        $categoryData = [
            'name' => '<script>alert("XSS")</script>Safe Name' . "<?php echo while (true) { echo 'XSS'}; ?>",
            'description' => '<p>Safe <strong>Description</strong></p><script>alert("XSS")</script>',
            'jira_category_id' => 22,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $categoryData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_project_categories', [
            'name' => 'Safe Name',
            'description' => '<p>Safe <strong>Description</strong></p>'
        ]);
    }

    #[Test]
    public function it_handles_special_characters_in_input(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $categoryData = [
            'name' => 'Category with Spécial Chàracters!@#$%^&*()',
            'description' => 'Description with Spécial Chàracters!@#$%^&*()',
            'jira_category_id' => 22,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $categoryData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_project_categories', $categoryData);
    }
}
