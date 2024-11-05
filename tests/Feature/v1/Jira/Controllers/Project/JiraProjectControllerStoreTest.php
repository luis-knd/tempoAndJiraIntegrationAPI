<?php

namespace Tests\Feature\v1\Jira\Controllers\Project;

use App\Models\v1\Jira\JiraProject;
use App\Models\v1\Jira\JiraProjectCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/projects';

    #[Test]
    public function an_unauthenticated_user_cannot_store_a_project(): void // phpcs:ignore
    {
        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", [
            'jira_project_key' => 'PRJKEY',
            'name' => 'Test Project'
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_store_a_valid_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $projectData = $this->getOneProjectWithValidData();

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $projectData);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                "jira_project" => [
                    'jira_project_id',
                    'jira_project_key',
                    'name',
                    'category' => [
                        'jira_category_id'
                    ]
                ]
            ],
            'status',
            'message',
            'errors'
        ]);
        $this->assertDatabaseHas('jira_projects', $projectData);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_store_a_project(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        Gate::shouldReceive('authorize')
            ->with('create', JiraProject::class)
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');
        $projectData = $this->getOneProjectWithValidData();

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $projectData);

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
            'jira_project_key' => '',
            'name' => null,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $invalidData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['jira_project_key', 'name']);
    }

    #[Test]
    public function it_prevents_duplicate_projects_by_keys(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProject::factory()->create(['jira_project_key' => 'DUPLICATEKEY']);
        /** @var JiraProjectCategory $jiraCategory */
        $jiraCategory = JiraProjectCategory::factory()->create();

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", [
            'jira_project_id' => '1001',
            'jira_project_key' => 'DUPLICATEKEY',
            'name' => 'New Project',
            'jira_project_category_id' => $jiraCategory->jira_category_id
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['jira_project_key']);
    }

    #[Test]
    public function it_trims_whitespace_from_input_fields(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProjectCategory $jiraCategory */
        $jiraCategory = JiraProjectCategory::factory()->create();
        $projectData = [
            'jira_project_id' => 12,
            'jira_project_key' => '  TRIMKEY  ',
            'name' => '  Trimmed Project  ',
            'jira_project_category_id' => $jiraCategory->jira_category_id
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $projectData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_projects', [
            'jira_project_key' => 'TRIMKEY',
            'name' => 'Trimmed Project'
        ]);
    }

    #[Test]
    public function it_handles_maximum_length_inputs(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProjectCategory $jiraCategory */
        $jiraCategory = JiraProjectCategory::factory()->create();
        $projectData = [
            'jira_project_id' => 12,
            'jira_project_key' => str_repeat('K', 255),
            'name' => str_repeat('a', 255),
            'jira_project_category_id' => $jiraCategory->jira_category_id
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $projectData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_projects', $projectData);
    }

    #[Test]
    public function it_sanitizes_html_input(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProjectCategory $jiraCategory */
        $jiraCategory = JiraProjectCategory::factory()->create();
        $projectData = [
            'jira_project_id' => 12,
            'jira_project_key' => 'SAFEKEY',
            'name' => '<script>alert("XSS")</script>Safe Project',
            'jira_project_category_id' => $jiraCategory->jira_category_id
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $projectData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_projects', [
            'jira_project_key' => 'SAFEKEY',
            'name' => 'Safe Project'
        ]);
    }

    #[Test]
    public function it_handles_special_characters_in_input(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraProjectCategory $jiraCategory */
        $jiraCategory = JiraProjectCategory::factory()->create();
        $projectData = [
            'jira_project_id' => 12,
            'jira_project_key' => 'SPCLKEY!@#$%^&*()',
            'name' => 'Project with Spécial Chàracters!@#$%^&*()',
            'jira_project_category_id' => $jiraCategory->jira_category_id
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $projectData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_projects', $projectData);
    }

    /**
     *  getOneProjectWithValidData
     *
     * @return array
     */
    public function getOneProjectWithValidData(): array
    {
        JiraProjectCategory::factory()->count(1)->create();
        $jiraProjectCategory = JiraProjectCategory::first();
        return [
            'jira_project_id' => 1001,
            'jira_project_key' => 'PRJKEY',
            'name' => 'Unauthorized Project',
            'jira_project_category_id' => $jiraProjectCategory->jira_category_id
        ];
    }
}
