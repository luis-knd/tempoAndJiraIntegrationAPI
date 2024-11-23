<?php

namespace Tests\Feature\v1\Tempo\Controllers;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Tempo\TimeEntry;
use App\Services\v1\Tempo\TimeEntryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TimeEntryControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'tempo/time-entries';

    #[Test]
    public function an_unauthenticated_user_cannot_update_a_time_entry(): void // phpcs:ignore
    {
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();
        $updateData = [
            'tempo_worklog_id' => $timeEntry->tempo_worklog_id,
            'time_spent_in_minutes' => 150,
            'description' => 'Updated description',
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id", $updateData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_update_a_time_entry(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->count(1)->create([
            'time_spent_in_minutes' => 120,
            'description' => 'Original description',
        ])->first();
        $updateData = [
            'time_spent_in_minutes' => 150,
            'description' => 'Updated description',
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id", $updateData);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'time_entries' => [
                    'tempo_worklog_id' => $timeEntry->tempo_worklog_id,
                    'issue' => ['jira_issue_id' => $timeEntry->jira_issue_id],
                    'user' => ['jira_user_id' => $timeEntry->jira_user_id],
                    'time_spent_in_minutes' => 150,
                    'description' => 'Updated description',
                    'entry_created_at' => $timeEntry->entry_created_at,
                    'entry_updated_at' => $timeEntry->entry_updated_at,
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
        $this->assertDatabaseHas('time_entries', $updateData);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_update_a_time_entry(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('update', TimeEntry::class)
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $updateData = [
            'tempo_worklog_id' => $timeEntry->tempo_worklog_id,
            'time_spent_in_minutes' => 150,
            'description' => 'Unauthorized update',
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id", $updateData);

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
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();
        $invalidData = [
            'tempo_worklog_id' => null,
            'time_spent_in_minutes' => -50,
            'description' => '',
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id", $invalidData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['time_spent_in_minutes', 'description']);
    }

    #[Test]
    public function it_handles_unprocessable_exception_during_update(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();
        $mockService = Mockery::mock(TimeEntryService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('update')
            ->with(Mockery::on(static function ($arg) use ($timeEntry) {
                return $arg->is($timeEntry);
            }), Mockery::any())
            ->andThrow(
                new UnprocessableException(
                    "Invalid parameters provided.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        $this->app->instance(TimeEntryService::class, $mockService);
        $updateData = [
            'tempo_worklog_id' => $timeEntry->tempo_worklog_id,
            'time_spent_in_minutes' => 150,
            'description' => 'Updated description',
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'Invalid parameters provided.',
            'errors' => [
                'params' => 'Invalid parameters provided.'
            ]
        ]);
    }

    #[Test]
    public function and_authenticated_user_cannot_update_sensitive_fields(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->count(1)->create([
            'time_spent_in_minutes' => 120,
            'description' => 'Original description',
        ])->first();
        $updateData = [
            'tempo_worklog_id' => 'invalid-tempo-worklog-id',
            'jira_issue_id' => 'invalid-jira-issue-id',
            'jira_user_id' => 'invalid-jira-user-id',
            'time_spent_in_minutes' => 150,
            'description' => 'Updated description',
            'entry_created_at' => '2024-10-01 10:00:00',
            'entry_updated_at' => '2024-11-11 10:00:00',
        ];

        $response = $this->putJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id", $updateData);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'time_entries' => [
                    'tempo_worklog_id' => $timeEntry->tempo_worklog_id,
                    'issue' => ['jira_issue_id' => $timeEntry->jira_issue_id],
                    'user' => ['jira_user_id' => $timeEntry->jira_user_id],
                    'time_spent_in_minutes' => 150,
                    'description' => 'Updated description',
                    'entry_created_at' => $timeEntry->entry_created_at,
                    'entry_updated_at' => $timeEntry->entry_updated_at,
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }
}
