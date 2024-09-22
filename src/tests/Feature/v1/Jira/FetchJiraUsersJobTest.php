<?php

namespace Tests\Feature\v1\Jira;

use App\Jobs\v1\Jira\FetchJiraUsersJob;
use App\Services\v1\Jira\JiraApiService;
use Database\Seeders\v1\Jira\JiraUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchJiraUsersJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_should_fetch_and_store_jira_users_from_real_api(): void // phpcs:ignore
    {
        self::markTestSkipped("If you want to test the real API, run this test. And adapt in base for your real data");
        $jiraApiService = resolve(JiraApiService::class);
        $job = new FetchJiraUsersJob();

        $job->handle($jiraApiService);

        $this->assertDatabaseHas('jira_users', [
            'jira_user_id' => '557058:fb745e64-a430-4aa7-8bcc-5a240d85b65b',
            'name' => 'Luis Candelario',
            'email' => 'lcandelario@lcandesign.com',
            'jira_user_type' => 'atlassian',
            'active' => 1
        ]);
        $this->assertDatabaseCount('jira_users', 11884);
    }

    #[Test]
    public function it_should_fetch_and_store_jira_users_from_mock_api(): void  // phpcs:ignore
    {

        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $this->seed(JiraUsersSeeder::class);
        $jiraApiServiceMock->method('fetchUsers')->willReturnOnConsecutiveCalls(
            [
                [
                    'accountId' => '557058:fb745e64-a430-4aa7-8bcc-5a240d85b65b',
                    'displayName' => 'Luis Candelario',
                    'emailAddress' => 'lcandelario@lcandesign.com',
                    'accountType' => 'atlassian',
                    'active' => 1
                ]
            ],
            []
        );

        $job = new FetchJiraUsersJob();
        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseCount('jira_users', 126);
        $this->assertDatabaseHas('jira_users', [
            'jira_user_id' => '557058:fb745e64-a430-4aa7-8bcc-5a240d85b65b',
            'name' => 'Luis Candelario',
            'email' => 'lcandelario@lcandesign.com',
            'jira_user_type' => 'atlassian',
            'active' => 1
        ]);
    }

    #[Test]
    public function it_should_throw_exception_for_atlassian_user_with_missing_data(): void // phpcs:ignore
    {

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'BadRequestException in FetchJiraProjectsJob: Invalid users data');
            });
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $jiraApiServiceMock->method('fetchUsers')->willReturnOnConsecutiveCalls(
            [
                [
                    'accountId' => '557058:missing-optional-fields',
                    'displayName' => null, // Falta displayName
                    'emailAddress' => 'missing@data.com',
                    'accountType' => 'atlassian',
                    'active' => 1
                ]
            ],
            []
        );

        $job = new FetchJiraUsersJob();
        $job->handle($jiraApiServiceMock);
    }

    #[Test]
    public function it_should_handle_empty_jira_users_response(): void  // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $jiraApiServiceMock->method('fetchUsers')->willReturn([]);

        $job = new FetchJiraUsersJob();
        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseCount('jira_users', 0);
    }

    #[Test]
    public function it_should_handle_users_with_missing_optional_fields_and_account_type_is_atlassian(): void // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $jiraApiServiceMock->method('fetchUsers')->willReturnOnConsecutiveCalls(
            [
                [
                    'accountId' => '557058:missing-optional-fields',
                    'displayName' => 'Luis Candelario',
                    'emailAddress' => null,
                    'accountType' => 'atlassian',
                    'active' => 1
                ]
            ],
            []
        );

        $job = new FetchJiraUsersJob();
        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseHas('jira_users', [
            'jira_user_id' => '557058:missing-optional-fields',
            'name' => 'Luis Candelario',
            'email' => 'sinCorreo@sinEmail.com',
            'jira_user_type' => 'atlassian',
            'active' => 1
        ]);
    }

    #[Test]
    public function it_should_handle_users_with_missing_optional_fields_and_account_type_is_customer(): void // phpcs:ignore
    {
        $jiraApiServiceMock = $this->createMock(JiraApiService::class);
        $jiraApiServiceMock->method('fetchUsers')->willReturnOnConsecutiveCalls(
            [
                [
                    'accountId' => '557058:missing-optional-fields',
                    'displayName' => null,
                    'emailAddress' => null,
                    'accountType' => 'customer',
                    'active' => 1
                ]
            ],
            []
        );

        $job = new FetchJiraUsersJob();
        $job->handle($jiraApiServiceMock);

        $this->assertDatabaseHas('jira_users', [
            'jira_user_id' => '557058:missing-optional-fields',
            'name' => 'sinNombre',
            'email' => 'sinCorreo@sinEmail.com',
            'jira_user_type' => 'customer',
            'active' => 1
        ]);
    }
}
