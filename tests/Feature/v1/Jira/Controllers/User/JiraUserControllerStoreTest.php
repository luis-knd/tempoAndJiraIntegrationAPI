<?php

namespace Tests\Feature\v1\Jira\Controllers\User;

use App\Models\v1\Jira\JiraUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraUserControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/users';

    #[Test]
    public function an_unauthenticated_user_cannot_store_a_user(): void // phpcs:ignore
    {
        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", [
            'jira_user_id' => 'test_user_1',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'jira_user_type' => 'admin',
            'active' => true,
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_store_a_valid_user(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $userData = [
            'jira_user_id' => 'test_user_1',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'jira_user_type' => 'user',
            'active' => true,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $userData);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'jira_user' => [
                    'jira_user_id',
                    'name',
                    'email',
                    'jira_user_type',
                    'active'
                ]
            ],
            'status',
            'message',
            'errors'
        ]);
        $this->assertDatabaseHas('jira_users', $userData);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_store_a_user(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        Gate::shouldReceive('authorize')
            ->with('create', JiraUser::class)
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');
        $userData = [
            'jira_user_id' => 'test_user_1',
            'name' => 'Unauthorized User',
            'email' => 'unauth@example.com',
            'jira_user_type' => 'user',
            'active' => true,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $userData);

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
            'jira_user_id' => '',
            'email' => 'not-a-valid-email',
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $invalidData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['jira_user_id', 'email']);
    }

    #[Test]
    public function it_prevents_duplicate_users_by_jira_user_ids(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraUser::factory()->create(['jira_user_id' => 'test_user_1']);

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", [
            'jira_user_id' => 'test_user_1',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'jira_user_type' => 'user',
            'active' => true,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['jira_user_id']);
    }

    #[Test]
    public function it_prevents_duplicate_users_by_email(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraUser::factory()->create(['email' => 'lcandelario@lcandesign.com']);

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", [
            'jira_user_id' => 'test_user_1',
            'name' => 'New User',
            'email' => 'lcandelario@lcandesign.com',
            'jira_user_type' => 'user',
            'active' => true,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_trims_whitespace_from_input_fields(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $userData = [
            'jira_user_id' => 'test_user_1',
            'name' => '  Trimmed User  ',
            'email' => ' trimmed@example.com ',
            'jira_user_type' => 'user',
            'active' => true,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $userData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_users', [
            'jira_user_id' => 'test_user_1',
            'name' => 'Trimmed User',
            'email' => 'trimmed@example.com'
        ]);
    }

    #[Test]
    public function it_sanitizes_html_input(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $userData = [
            'jira_user_id' => 'test_user_1',
            'name' => '<script>alert("XSS")</script>Safe User',
            'email' => '<p>safe@example.com</p>',
            'jira_user_type' => '<b>admin</b>',
            'active' => true,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $userData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_users', [
            'jira_user_id' => 'test_user_1',
            'name' => 'Safe User',
            'email' => 'safe@example.com',
            'jira_user_type' => 'admin'
        ]);
    }

    #[Test]
    public function it_handles_special_characters_in_input(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $userData = [
            'jira_user_id' => 'special_user_1',
            'name' => 'User with Spécial Chàracters!@#$%^&*()',
            'email' => 'special@example.com',
            'jira_user_type' => 'admin',
            'active' => true,
        ];

        $response = $this->postJson("$this->apiBaseUrl/$this->urlPath", $userData);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('jira_users', $userData);
    }
}
