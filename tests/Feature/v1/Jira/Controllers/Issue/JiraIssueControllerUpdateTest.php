<?php

namespace Feature\v1\Jira\Controllers\Issue;

use App\Models\v1\Jira\JiraIssue;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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
        $response->assertJsonFragment([
            'data' => [
                'jira_issue' => [
                    'jira_issue_id' => $jiraIssue->jira_issue_id, // @phpstan-ignore-line
                    'jira_issue_key' => $jiraIssue->jira_issue_key, // @phpstan-ignore-line
                    'project' => [
                        'jira_project_id' => $jiraIssue->jira_project_id, // @phpstan-ignore-line
                    ],
                    'summary' => 'Updated summary',
                    'development_category' => 'Refactor',
                    'status' => 'In progress',
                    'created_at' => $jiraIssue->created_at, // @phpstan-ignore-line
                    'updated_at' => $jiraIssue->updated_at // @phpstan-ignore-line
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
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

        // @phpstan-ignore-next-line
        $response = $this->putJson("$this->apiBaseUrl/jira/issues/$jiraIssue->id", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_issue' => [
                    'jira_issue_id' => 'OLD-123',
                    'jira_issue_key' => 'LCD',
                    'project' => [
                        'jira_project_id' => $jiraIssue->jira_project_id, // @phpstan-ignore-line
                    ],
                    'summary' => 'Updated summary',
                    'development_category' => 'Refactor',
                    'status' => 'In progress',
                    'created_at' => $jiraIssue->created_at, // @phpstan-ignore-line
                    'updated_at' => $jiraIssue->updated_at // @phpstan-ignore-line
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);

        $this->assertDatabaseHas('jira_issues', [
            'id' => $jiraIssue->id, // @phpstan-ignore-line
            'jira_issue_id' => 'OLD-123',
        ]);

        $this->assertDatabaseMissing('jira_issues', [
            'id' => $jiraIssue->id, // @phpstan-ignore-line
            'jira_issue_id' => 'NEW-456',
        ]);
    }
}
