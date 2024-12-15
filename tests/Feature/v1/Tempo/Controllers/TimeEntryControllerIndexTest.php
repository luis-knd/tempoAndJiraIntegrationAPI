<?php

namespace Tests\Feature\v1\Tempo\Controllers;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TimeEntryControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_get_time_entries(): void // phpcs:ignore
    {
        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_get_time_entries(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        TimeEntry::factory()->count(3)->create();

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'time_entries' => [
                    '*' => [
                        'tempo_worklog_id',
                        'issue' => ['jira_issue_id'],
                        'user' => ['jira_user_id'],
                        'time_spent_in_minutes',
                        'description',
                        'entry_created_at',
                        'entry_updated_at',
                    ],
                ],
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages',
            ],
            'status',
            'message',
            'errors',
        ]);
    }

    #[Test]
    public function an_authenticated_user_gets_an_empty_list_when_there_are_no_time_entries(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'time_entries' => [],
                'total' => 0,
                'count' => 0,
                'per_page' => 30,
                'current_page' => 1,
                'total_pages' => 1,
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_invalid_pagination_parameters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries?page=-1&page_size=1000");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The page size field must not be greater than 100. (and 1 more error)',
            'errors' => [
                'page' => ['The page field must be at least 1.'],
                'page_size' => ['The page size field must not be greater than 100.'],
            ],
        ]);
    }

    #[Test]
    public function it_can_filter_time_entries_by_jira_issue_id(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        JiraIssue::factory()->count(2)->create();
        /** @var JiraIssue $jiraIssue */
        $jiraIssue = JiraIssue::first();
        /** @var JiraIssue $otherJiraIssue */
        $otherJiraIssue = JiraIssue::skip(1)->first();
        TimeEntry::factory()->create(['jira_issue_id' => $jiraIssue->jira_issue_id]);
        TimeEntry::factory()->create(['jira_issue_id' => $otherJiraIssue->jira_issue_id]);

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries?jira_issue_id[ne]=$jiraIssue->jira_issue_id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['jira_issue_id' => $otherJiraIssue->jira_issue_id]);
        $this->assertCount(1, $response->json('data.time_entries'));
    }

    #[Test]
    public function it_can_sort_time_entries_by_entry_created_at(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        TimeEntry::factory()->count(1)->create(['entry_created_at' => '2024-01-01 00:00:00']);
        TimeEntry::factory()->count(1)->create(['entry_created_at' => '2024-05-06 10:30:00']);
        TimeEntry::factory()->count(1)->create(['entry_created_at' => '2019-04-11 22:19:30']);

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries?sort_by=entry_created_at&sort_order=asc");

        $response->assertStatus(Response::HTTP_OK);
        $responseData = $response->json('data.time_entries');
        $this->assertGreaterThan(2, count($responseData));
        $this->assertTrue($responseData[0]['entry_created_at'] <= $responseData[1]['entry_created_at']);
    }

    #[Test]
    public function it_handles_invalid_filter_parameters(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries?relations=invalid_relation");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The invalid_relation is present in relations param but is not available hydration',
            'errors' => [
                'relations' => ['The invalid_relation is present in relations param but is not available hydration'],
            ],
        ]);
    }

    #[Test]
    public function it_can_filter_time_entries_by_time_spent_in_minutes_range(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        TimeEntry::factory()->count(10)->create(['time_spent_in_minutes' => 30]);
        TimeEntry::factory()->count(10)->create(['time_spent_in_minutes' => 60]);
        TimeEntry::factory()->count(10)->create(['time_spent_in_minutes' => 180]);
        TimeEntry::factory()->count(15)->create(['time_spent_in_minutes' => 240]);

        //$filter = "time_spent_in_minutes[gte]=45&time_spent_in_minutes[lte]=180";
        $filter = "time_spent_in_minutes[between]=45,180";
        $response = $this->getJson("$this->apiBaseUrl/tempo/time-entries?$filter");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(20, $response->json('data.time_entries'));
        $this->assertEquals(20, $response->json('data.count'));
        $this->assertEquals(20, $response->json('data.total'));
        $response->assertJsonFragment(['time_spent_in_minutes' => 60]);
        $response->assertJsonFragment(['time_spent_in_minutes' => 180]);
    }
}
