<?php

namespace Tests\Feature\v1\Jira\Controllers;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Jira\JiraProject;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraIssueControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_create_an_issue(): void // phpcs:ignore
    {
        $response = $this->postJson("$this->apiBaseUrl/jira/issues", []);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_create_an_issue_with_valid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProject = JiraProject::factory()->create()->first();
        $payload = [
            'jira_issue_key' => 'PROJ-101',
            'jira_issue_id' => 999,
            'jira_project_id' => $jiraProject->jira_project_id, //@phpstan-ignore-line
            'summary' => 'New issue for project',
            'development_category' => 'New feature',
            'status' => 'New',
        ];

        $response = $this->postJson("$this->apiBaseUrl/jira/issues", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'data' => [
                'jira_issue' => [
                    'jira_issue_id' => 999,
                    'jira_issue_key' => 'PROJ-101',
                    'project' => [
                        'jira_project_id' => $jiraProject->jira_project_id, //@phpstan-ignore-line
                    ],
                    'summary' => 'New issue for project',
                    'development_category' => 'New feature',
                    'status' => 'New',
                    'created_at' => $response->json('data.jira_issue.created_at'),
                    'updated_at' => $response->json('data.jira_issue.updated_at'),
                ],
            ],
            'status' => Response::HTTP_OK,
            'message' => 'OK',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_create_an_issue_with_invalid_data(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $payload = [
            'jira_issue_key' => '',
            'jira_project_id' => null,
            'summary' => '',
        ];

        $response = $this->postJson("$this->apiBaseUrl/jira/issues", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The jira issue id field is required. (and 4 more errors)',
            'errors' => [
                'jira_issue_id' => ['The jira issue id field is required.'],
                'jira_issue_key' => ['The jira issue key field is required.'],
                'jira_project_id' => ['The jira project id field is required.'],
                'status' => ['The status field is required.'],
                'summary' => ['The summary field is required.'],
            ]
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_create_an_issue(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProject = JiraProject::factory()->create()->first();
        Gate::shouldReceive('authorize')
            ->with('create', JiraIssue::class)
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $payload = [
            'jira_issue_key' => 'PROJ-102',
            'jira_issue_id' => 999,
            'jira_project_id' => $jiraProject->jira_project_id, //@phpstan-ignore-line
            'summary' => 'Unauthorized issue creation',
            'development_category' => 'Bug',
            'status' => 'Open',
        ];

        $response = $this->postJson("$this->apiBaseUrl/jira/issues", $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_prevents_creating_a_duplicate_issue(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraProject = JiraProject::factory()->create()->first();
        JiraIssue::factory()->create([
            'jira_issue_key' => 'PROJ-105',
        ]);

        $payload = [
            'jira_issue_key' => 'PROJ-105',
            'jira_issue_id' => 999,
            'jira_project_id' => $jiraProject->jira_project_id, //@phpstan-ignore-line
            'summary' => 'Unauthorized issue creation',
            'development_category' => 'Bug',
            'status' => 'Open',
        ];

        $response = $this->postJson("$this->apiBaseUrl/jira/issues", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'The jira issue key has already been taken.',
            'errors' => [
                'jira_issue_key' => ['The jira issue key has already been taken.']
            ]
        ]);
    }
}
