<?php

namespace Tests\Feature\v1\Jira\Controllers\ProjectCategory;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraProjectCategory;
use App\Services\v1\Jira\JiraProjectCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraProjectCategoryControllerShowTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'jira/project-categories';

    #[Test]
    public function an_unauthenticated_user_cannot_get_a_project_category(): void // phpcs:ignore
    {
        $response = $this->getJson($this->apiBaseUrl . "/$this->urlPath/1");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_a_project_category(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraProjectCategory::factory()->count(4)->create();
        $jiraProjectCategory = JiraProjectCategory::first();
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_project_category' => [
                    'jira_category_id' => $jiraProjectCategory->jira_category_id,
                    'name' => $jiraProjectCategory->name,
                    'description' => $jiraProjectCategory->description
                ]
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_an_invalid_project_category(): void // phpcs:ignore
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
    public function multiple_users_can_access_the_same_project_category_simultaneously(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        $responses = collect(range(1, 10))->map(function () use ($jiraProjectCategory) {
            // @phpstan-ignore-next-line
            return $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id");
        });

        $responses->each(function ($response) {
            $response->assertStatus(Response::HTTP_OK);
        });
    }

    #[Test]
    public function an_authenticated_user_can_request_specific_fields(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();

        // @phpstan-ignore-next-line
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id?fields=name,description");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'jira_project_category' => [
                    'name',
                    'description'
                ]
            ]
        ]);
        $response->assertJsonMissing(['jira_category_id']);
    }

    #[Test]
    public function it_sanitizes_input_correctly(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();


        $response = $this->getJson(
        // @phpstan-ignore-next-line
            "$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id?name=<script>alert('xss')</script>"
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonMissing(['<script>']);
    }

    #[Test]
    public function it_handles_unprocessable_exception_correctly(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProjectCategory = JiraProjectCategory::factory()->create();
        $mockService = Mockery::mock(JiraProjectCategoryService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('load')
            ->with(Mockery::on(static function ($arg) use ($jiraProjectCategory) {
                return $arg->is($jiraProjectCategory);
            }), Mockery::any())
            ->andThrow(
                new UnprocessableException(
                    "Invalid parameters provided.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        $this->app->instance(JiraProjectCategoryService::class, $mockService);

        // @phpstan-ignore-next-line
        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath/$jiraProjectCategory->id?invalid_param=value");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'Invalid parameters provided.',
            'errors' => [
                'params' => 'Invalid parameters provided.'
            ]
        ]);
    }
}
