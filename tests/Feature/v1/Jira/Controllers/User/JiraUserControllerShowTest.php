<?php

namespace Tests\Feature\v1\Jira\Controllers\User;

use App\Models\v1\Jira\JiraUser;
use App\Services\v1\Jira\JiraUserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraUserControllerShowTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/users';

    #[Test]
    public function an_unauthenticated_user_cannot_get_a_user(): void // phpcs:ignore
    {
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();

        $response = $this->getJson($this->apiBaseUrl . "/$this->urlPath/$jiraUser->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_a_user(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id");

        $response->assertJson(function (AssertableJson $json) use ($jiraUser) {
            $json->where('status', Response::HTTP_OK)
                ->where('message', 'OK')
                ->where('errors', [])
                ->has('data.jira_user', function (AssertableJson $json) use ($jiraUser) {
                    $json->where('jira_user_id', $jiraUser->jira_user_id)
                        ->where('name', $jiraUser->name)
                        ->where('email', $jiraUser->email)
                        ->where('jira_user_type', $jiraUser->jira_user_type)
                        ->where('active', $jiraUser->active)
                        ->etc();
                });
        });
    }

    #[Test]
    public function an_authenticated_user_cannot_get_an_invalid_user(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $id = Str::uuid()->toString();
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$id");

        $response->assertJsonFragment([
            'data' => [],
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => "Resource $id not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function multiple_users_can_access_the_same_user_simultaneously(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();

        $responses = collect(range(1, 10))->map(function () use ($jiraUser) {
            return $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id");
        });

        $responses->each(function ($response) {
            $response->assertStatus(Response::HTTP_OK);
        });
    }

    #[Test]
    public function an_authenticated_user_can_request_specific_fields(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id?fields=name,email");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'jira_user' => [
                    'name',
                    'email'
                ]
            ]
        ]);
        $response->assertJsonMissing(['jira_user_id', 'jira_user_type', 'active']);
    }

    #[Test]
    public function it_sanitizes_input_correctly(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();

        $response = $this->getJson(
            "$this->apiBaseUrl/$this->urlPath/$jiraUser->id?name=<script>alert('xss')</script>"
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonMissing(['<script>']);
    }

    #[Test]
    public function it_handles_unprocessable_exception_correctly(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create();
        $mockService = Mockery::mock(JiraUserService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('load')
            ->with(Mockery::on(static function ($arg) use ($jiraUser) {
                return $arg->is($jiraUser);
            }), Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');
        $this->app->bind(JiraUserService::class, static function () use ($mockService) {
            return $mockService;
        });
        $this->app->instance(JiraUserService::class, $mockService);

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraUser->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }
}
