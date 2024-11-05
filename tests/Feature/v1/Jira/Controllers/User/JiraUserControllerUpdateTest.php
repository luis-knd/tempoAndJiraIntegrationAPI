<?php

namespace Tests\Feature\v1\Jira\Controllers\User;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraUser;
use App\Services\v1\Jira\JiraUserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraUserControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/users';

    #[Test]
    public function an_unauthenticated_user_cannot_update_a_user(): void // phpcs:ignore
    {
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        $updateData = [
            'name' => 'Updated User Name',
            'email' => 'updated@example.com',
            'jira_user_type' => 'user',
            'active' => true,
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id", $updateData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_update_a_user(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        $updateData = [
            'name' => 'Updated User Name',
            'email' => 'updated@example.com',
            'jira_user_type' => 'customer',
            'active' => false,
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id", $updateData);
        $response->assertJson(function (AssertableJson $json) use ($jiraUser) {
            $json->where('status', Response::HTTP_OK)
                ->where('message', 'OK')
                ->where('errors', [])
                ->has('data.jira_user', function (AssertableJson $json) use ($jiraUser) {
                    $json->where('jira_user_id', $jiraUser->jira_user_id)
                        ->where('name', 'Updated User Name')
                        ->where('email', 'updated@example.com')
                        ->where('jira_user_type', 'customer')
                        ->where('active', false)
                        ->etc();
                });
        });
        $this->assertDatabaseHas('jira_users', $updateData);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_a_jira_user_id(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        $updateData = [
            'jira_user_id' => 'updated-jira-user-id',
            'name' => 'Updated User Name',
            'email' => 'updated@example.com',
            'jira_user_type' => 'customer',
            'active' => false,
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id", $updateData);
        $response->assertJson(function (AssertableJson $json) use ($jiraUser) {
            $json->where('status', Response::HTTP_OK)
                ->where('message', 'OK')
                ->where('errors', [])
                ->has('data.jira_user', function (AssertableJson $json) use ($jiraUser) {
                    $json->where('jira_user_id', $jiraUser->jira_user_id)
                        ->where('name', 'Updated User Name')
                        ->where('email', 'updated@example.com')
                        ->where('jira_user_type', 'customer')
                        ->where('active', false)
                        ->etc();
                });
        });
        $this->assertDatabaseMissing('jira_users', ['jira_user_id' => $updateData['jira_user_id']]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_update_a_user(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('update', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $updateData = [
            'name' => 'Unauthorized Update',
            'email' => 'unauth@example.com',
            'jira_user_type' => 'user',
            'active' => true,
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id", $updateData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_returns_validation_errors_for_invalid_update_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        $invalidData = [
            'name' => '',
            'email' => 'invalid-email',
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id", $invalidData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['name', 'email']);
    }

    #[Test]
    public function it_handles_unprocessable_exception_during_update(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        $mockService = Mockery::mock(JiraUserService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('update')
            ->with(Mockery::on(static function ($arg) use ($jiraUser) {
                return $arg->is($jiraUser);
            }), Mockery::any())
            ->andThrow(
                new UnprocessableException(
                    "Invalid parameters provided.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        $this->app->instance(JiraUserService::class, $mockService);

        $updateData = [
            'name' => 'Updated User Name',
            'email' => 'updated@example.com',
            'jira_user_type' => 'user',
            'active' => false,
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'Invalid parameters provided.',
            'errors' => [
                'params' => 'Invalid parameters provided.'
            ]
        ]);
    }
}
