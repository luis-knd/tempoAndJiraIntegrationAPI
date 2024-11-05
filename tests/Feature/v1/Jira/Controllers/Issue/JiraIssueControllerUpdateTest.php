<?php

namespace Tests\Feature\v1\Jira\Controllers\Issue;

use App\Models\v1\Jira\JiraIssue;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraIssueControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_update_an_issue(): void // phpcs:ignore
    {
        $jiraIssue = JiraIssue::factory()->create();

        $payload = [
            'summary' => 'Updated summary',
            'development_category' => 'Refactor',
            'status' => 'In progress',
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/jira/issues/$jiraIssue->id", $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_update_an_issue(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraIssue = JiraIssue::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('update', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');
        $payload = [
            'summary' => 'Unauthorized update',
            'development_category' => 'Refactor',
            'status' => 'In progress',
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/jira/issues/$jiraIssue->id", $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_update_an_issue_with_valid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraIssue = JiraIssue::factory()->create();
        $payload = [
            'summary' => 'Updated summary',
            'development_category' => 'Refactor',
            'status' => 'In progress',
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/jira/issues/$jiraIssue->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        /** @var JiraIssue $jiraIssue */
        $response->assertJson(function (AssertableJson $json) use ($jiraIssue) {
            $json->where('status', Response::HTTP_OK)
                ->where('message', 'OK')
                ->where('errors', [])
                ->has('data.jira_issue', function (AssertableJson $json) use ($jiraIssue) {
                    $json->where('jira_issue_id', $jiraIssue->jira_issue_id)
                        ->where('jira_issue_key', $jiraIssue->jira_issue_key)
                        ->where('summary', 'Updated summary')
                        ->where('development_category', 'Refactor')
                        ->where('status', 'In progress')
                        ->where('project.jira_project_id', $jiraIssue->jira_project_id)
                        ->etc();
                });
        });
    }

    #[Test]
    public function an_authenticated_user_cannot_update_an_issue_with_invalid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraIssue = JiraIssue::factory()->create();
        $payload = [
            'summary' => '',
            'development_category' => '',
            'status' => '',
        ];

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/jira/issues/$jiraIssue->id", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The summary field is required. (and 2 more errors)',
            'errors' => [
                'summary' => ['The summary field is required.'],
                'development_category' => ['The development category field is required.'],
                'status' => ['The status field is required.'],
            ]
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_a_non_existent_issue(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $nonExistentIssueId = 9999;
        $payload = [
            'summary' => 'Updated summary for non-existent issue',
            'development_category' => 'Bugfix',
            'status' => 'Closed',
        ];

        $response = $this->putJson("$this->apiBaseUrl/jira/issues/$nonExistentIssueId", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => "Resource $nonExistentIssueId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_update_the_jira_issue_id_and_jira_issue_key(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraIssue $jiraIssue */
        $jiraIssue = JiraIssue::factory()->create([
            'jira_issue_id' => 'OLD-123',
            'jira_issue_key' => 'LCD',
        ]);
        $payload = [
            'jira_issue_id' => 'NEW-456',
            'jira_issue_key' => 'NEW-LCD',
            'summary' => 'Updated summary',
            'development_category' => 'Refactor',
            'status' => 'In progress',
        ];

        $response = $this->putJson("$this->apiBaseUrl/jira/issues/$jiraIssue->id", $payload);

        $response->assertJson(function (AssertableJson $json) use ($jiraIssue) {
            $json->where('status', Response::HTTP_OK)
                ->where('message', 'OK')
                ->where('errors', [])
                ->has('data.jira_issue', function (AssertableJson $json) use ($jiraIssue) {
                    $json->where('jira_issue_id', 'OLD-123')
                        ->where('jira_issue_key', 'LCD')
                        ->where('summary', 'Updated summary')
                        ->where('development_category', 'Refactor')
                        ->where('status', 'In progress')
                        ->where('project.jira_project_id', $jiraIssue->jira_project_id)
                        ->etc();
                });
        });

        $this->assertDatabaseHas('jira_issues', [
            'id' => $jiraIssue->id,
            'jira_issue_id' => 'OLD-123',
        ]);

        $this->assertDatabaseMissing('jira_issues', [
            'id' => $jiraIssue->id,
            'jira_issue_id' => 'NEW-456',
        ]);

        $createdAt = $response->json('data.jira_issue.created_at');
        $updatedAt = $response->json('data.jira_issue.updated_at');

        $this->assertTrue(
            Carbon::parse($updatedAt)->diffInSeconds(Carbon::parse($createdAt)) <= 2,
            'El campo updated_at no está dentro del rango esperado.'
        );
    }
}
