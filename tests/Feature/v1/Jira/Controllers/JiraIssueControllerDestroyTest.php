<?php

namespace Tests\Feature\v1\Jira\Controllers;

use App\Models\v1\Jira\JiraIssue;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class JiraIssueControllerDestroyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unauthenticated_user_cannot_delete_an_issue(): void // phpcs:ignore
    {
        $jiraIssue = JiraIssue::factory()->create();

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/jira/issues/{$jiraIssue->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_delete_an_issue(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraIssue = JiraIssue::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('delete', Mockery::any())
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/jira/issues/$jiraIssue->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_delete_an_issue_with_permission(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $jiraIssue = JiraIssue::factory()->create();

        // @phpstan-ignore-next-line
        $response = $this->deleteJson("$this->apiBaseUrl/jira/issues/$jiraIssue->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'message' => 'JiraIssue deleted successfully.',
            'errors' => []
        ]);
        $this->assertDatabaseMissing('jira_issues', [
            'id' => $jiraIssue->id, // @phpstan-ignore-line
        ]);
    }

    #[Test]
    public function an_authenticated_user_cannot_delete_a_non_existent_issue(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $nonExistentIssueId = 9999;

        $response = $this->deleteJson("$this->apiBaseUrl/jira/issues/$nonExistentIssueId");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => "Resource $nonExistentIssueId not found.",
            'errors' => []
        ]);
    }
}
