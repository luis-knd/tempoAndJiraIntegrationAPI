<?php

namespace Tests\Feature\v1\Tempo\Controllers;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Jira\JiraUser;
use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TimeEntryControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_create_a_time_entry(): void // phpcs:ignore
    {
        $payload = [
            'tempo_worklog_id' => 123,
            'jira_issue_id' => 456,
            'jira_user_id' => 'user-789',
            'time_spent_in_minutes' => 120,
            'description' => 'Worked on feature X.',
            'entry_created_at' => now()->toDateTimeString(),
            'entry_updated_at' => now()->toDateTimeString(),
        ];

        $response = $this->postJson("$this->apiBaseUrl/tempo/time-entries", $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_create_a_time_entry_with_valid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        [$jiraIssue, $jiraUser] = $this->generatedFakeIssueAndUserFromJira();

        $payload = [
            'tempo_worklog_id' => 123,
            'jira_issue_id' => $jiraIssue->jira_issue_id,
            'jira_user_id' => $jiraUser->jira_user_id,
            'time_spent_in_minutes' => 120,
            'description' => 'Worked on feature X.',
            'entry_created_at' => now()->toDateTimeString(),
            'entry_updated_at' => now()->toDateTimeString(),
        ];

        $response = $this->postJson("$this->apiBaseUrl/tempo/time-entries", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'time_entries' => [
                    'tempo_worklog_id' => 123,
                    'issue' => ['jira_issue_id' => $jiraIssue->jira_issue_id],
                    'user' => ['jira_user_id' => $jiraUser->jira_user_id],
                    'time_spent_in_minutes' => 120,
                    'description' => 'Worked on feature X.',
                    'entry_created_at' => now()->toDateTimeString(),
                    'entry_updated_at' => now()->toDateTimeString(),
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);

        $this->assertDatabaseHas('time_entries', $payload);
    }

    #[Test]
    public function an_authenticated_user_cannot_create_a_time_entry_with_invalid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $payload = [
            'tempo_worklog_id' => null,
            'jira_issue_id' => null,
            'jira_user_id' => null,
            'time_spent_in_minutes' => 'invalid',
            'description' => '',
            'entry_created_at' => null,
            'entry_updated_at' => null,
        ];

        $response = $this->postJson("$this->apiBaseUrl/tempo/time-entries", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The tempo worklog id field is required. (and 6 more errors)',
            'errors' => [
                'tempo_worklog_id' => ['The tempo worklog id field is required.'],
                'jira_issue_id' => ['The jira issue id field is required.'],
                'jira_user_id' => ['The jira user id field is required.'],
                'time_spent_in_minutes' => ['The time spent in minutes field must be a number.'],
                'description' => ['The description field is required.'],
                'entry_created_at' => ['The entry created at field is required.'],
                'entry_updated_at' => ['The entry updated at field is required.'],
            ]
        ]);
    }

    #[Test]
    public function it_prevents_duplicate_time_entries(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var JiraIssue $jiraIssue */
        [$jiraIssue, $jiraUser] = $this->generatedFakeIssueAndUserFromJira();


        $payload = [
            'tempo_worklog_id' => 123,
            'jira_issue_id' => $jiraIssue->jira_issue_id,
            'jira_user_id' => $jiraUser->jira_user_id,
            'time_spent_in_minutes' => 120,
            'description' => 'Worked on feature X.',
            'entry_created_at' => now()->toDateTimeString(),
            'entry_updated_at' => now()->toDateTimeString(),
        ];

        TimeEntry::factory()->create($payload);

        $response = $this->postJson("$this->apiBaseUrl/tempo/time-entries", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'The tempo worklog id has already been taken.',
            'errors' => [
                'tempo_worklog_id' => ['The tempo worklog id has already been taken.']
            ]
        ]);
    }

    #[Test]
    public function it_sanitizes_html_input(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        [$jiraIssue, $jiraUser] = $this->generatedFakeIssueAndUserFromJira();

        $payload = [
            'tempo_worklog_id' => 123,
            'jira_issue_id' => $jiraIssue->jira_issue_id,
            'jira_user_id' => $jiraUser->jira_user_id,
            'time_spent_in_minutes' => 120,
            'description' => '<p>Worked on <script>alert("XSS")</script>feature X.</p>',
            'entry_created_at' => now()->toDateTimeString(),
            'entry_updated_at' => now()->toDateTimeString(),
        ];

        $response = $this->postJson("$this->apiBaseUrl/tempo/time-entries", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'time_entries' => [
                    'tempo_worklog_id' => 123,
                    'issue' => ['jira_issue_id' => $jiraIssue->jira_issue_id],
                    'user' => ['jira_user_id' => $jiraUser->jira_user_id],
                    'time_spent_in_minutes' => 120,
                    'description' => '<p>Worked on feature X.</p>',
                    'entry_created_at' => now()->toDateTimeString(),
                    'entry_updated_at' => now()->toDateTimeString(),
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);

        $this->assertDatabaseHas('time_entries', [
            'description' => '<p>Worked on feature X.</p>',
        ]);
    }

    public function generatedFakeIssueAndUserFromJira(): array
    {
        /** @var JiraIssue $jiraIssue */
        $jiraIssue = JiraIssue::factory()->create(['jira_issue_id' => 456])->first();
        /** @var JiraUser $jiraUser */
        $jiraUser = JiraUser::factory()->create(['jira_user_id' => 'user-789'])->first();

        return [$jiraIssue, $jiraUser];
    }
}
