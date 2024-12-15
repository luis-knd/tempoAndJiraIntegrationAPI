<?php

namespace Tests\Feature\v1\Tempo\Controllers;

use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TimeEntryControllerShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_view_a_time_entry(): void // phpcs:ignore
    {
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create()->first();

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries/$timeEntry->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_view_a_time_entry(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create()->first();

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries/$timeEntry->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'time_entries' => [
                    'tempo_worklog_id' => $timeEntry->tempo_worklog_id,
                    'issue' => ['jira_issue_id' => $timeEntry->jira_issue_id],
                    'user' => ['jira_user_id' => $timeEntry->jira_user_id],
                    'time_spent_in_minutes' => $timeEntry->time_spent_in_minutes,
                    'description' => $timeEntry->description,
                    'entry_created_at' => $timeEntry->entry_created_at,
                    'entry_updated_at' => $timeEntry->entry_updated_at,
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_view_a_time_entry(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();

        Gate::shouldReceive('authorize')
            ->with('view', TimeEntry::class)
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries/$timeEntry->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_view_a_non_existent_time_entry(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $nonExistentId = 'non-existent-id';

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries/$nonExistentId");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => "Resource $nonExistentId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_invalid_relations_in_request(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();

        $invalidParam = 'invalid_relation';

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries/$timeEntry->id?relations=$invalidParam");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => "The $invalidParam is present in relations param but is not available hydration",
            'errors' => [
                'relations' => ["The $invalidParam is present in relations param but is not available hydration"],
            ],
        ]);
    }

    #[Test]
    public function it_handles_valid_relations_correctly(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();
        $timeEntry->load('issue', 'jiraUser');

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries/$timeEntry->id?relations=issue,jira_user");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'time_entries' => [
                    'tempo_worklog_id',
                    'description',
                    'time_spent_in_minutes',
                    'issue' => [
                        'jira_issue_id',
                        'jira_issue_key',
                        'status',
                        'summary',
                        'development_category',
                        'project' => ['jira_project_id'],
                        'created_at',
                        'updated_at',
                    ],
                    'user' => [
                        'id',
                        'jira_user_id',
                        'jira_user_type',
                        'name',
                        'email',
                        'active',
                        'created_at',
                        'updated_at',
                    ],
                    'entry_created_at',
                    'entry_updated_at',
                ],
            ],
            'status',
            'message',
            'errors',
        ]);
        $response->assertJson([
            'data' => [
                'time_entries' => [
                    'tempo_worklog_id' => $timeEntry->tempo_worklog_id,
                    'time_spent_in_minutes' => $timeEntry->time_spent_in_minutes,
                    'description' => $timeEntry->description,
                    'entry_created_at' => $timeEntry->entry_created_at,
                    'entry_updated_at' => $timeEntry->entry_updated_at,
                    'issue' => [
                        'jira_issue_id' => $timeEntry->issue->jira_issue_id,
                        'jira_issue_key' => $timeEntry->issue->jira_issue_key,
                        'project' => ['jira_project_id' => $timeEntry->issue->jira_project_id],
                        'status' => $timeEntry->issue->status,
                        'summary' => $timeEntry->issue->summary,
                        'development_category' => $timeEntry->issue->development_category,
                        'created_at' => $timeEntry->issue->created_at->toISOString(),
                        'updated_at' => $timeEntry->issue->updated_at->toISOString(),
                    ],
                    'user' => [
                        'id' => $timeEntry->jiraUser->id,
                        'jira_user_id' => $timeEntry->jiraUser->jira_user_id,
                        'jira_user_type' => $timeEntry->jiraUser->jira_user_type,
                        'name' => $timeEntry->jiraUser->name,
                        'email' => $timeEntry->jiraUser->email,
                        'active' => (bool)$timeEntry->jiraUser->active,
                        'created_at' => $timeEntry->jiraUser->created_at->toISOString(),
                        'updated_at' => $timeEntry->jiraUser->updated_at->toISOString(),
                    ],
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }
}
