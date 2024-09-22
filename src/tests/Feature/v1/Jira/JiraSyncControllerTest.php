<?php

namespace Tests\Feature\v1\Jira;

use App\Jobs\v1\Jira\FetchJiraIssuesJob;
use App\Models\v1\Basic\User;
use Database\Seeders\v1\Basic\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    #[Test]
    public function it_cannot_sync_without_auth(): void // phpcs:ignore
    {
        $response = $this->postJson("$this->apiBaseUrl/jira/sync-all");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(
            ['data' => [], 'status' => Response::HTTP_UNAUTHORIZED, 'message' => 'Unauthenticated.']
        );
    }

    #[Test]
    public function it_should_dispatch_fetch_jira_issues_job(): void // phpcs:ignore
    {
        Queue::fake();
        $user = User::first();

        $jql = ['jql' => 'filter = "Issues 2.0"'];
        $response = $this->apiAs(user: $user, method: 'post', uri: "$this->apiBaseUrl/jira/sync-all", data: $jql);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['message' => 'Jira sync started']);
        Queue::assertPushed(FetchJiraIssuesJob::class, static function (FetchJiraIssuesJob $job) {
            return $job->getJql() === 'filter = "Issues 2.0"';
        });
    }
}
