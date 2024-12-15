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

class TimeEntryControllerDestroyTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'tempo/time-entries';

    #[Test]
    public function an_unauthenticated_user_cannot_delete_a_time_entry(): void // phpcs:ignore
    {
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unauthenticated.',
            'errors' => []
        ]);
    }

    #[Test]
    public function an_authenticated_user_can_delete_a_time_entry(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'message' => 'TimeEntry deleted successfully.',
            'errors' => []
        ]);
        $this->assertDatabaseMissing('time_entries', ['id' => $timeEntry->id]);
    }

    #[Test]
    public function an_authenticated_user_without_permission_cannot_delete_a_time_entry(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();
        Gate::shouldReceive('authorize')
            ->with('delete', TimeEntry::class)
            ->andThrow(AuthorizationException::class, 'This action is unauthorized.');

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment([
            'message' => 'This action is unauthorized.',
            'errors' => []
        ]);
    }

    #[Test]
    public function it_returns_an_error_when_deleting_a_non_existent_time_entry(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();

        $nonExistentId = 'non-existent-id';

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$nonExistentId");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => "Resource $nonExistentId not found.",
            'errors' => []
        ]);
    }

    #[Test]
    public function it_handles_unprocessable_exception_during_deletion(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();
        $mockService = Mockery::mock(TimeEntryService::class);
        // @phpstan-ignore-next-line
        $mockService->shouldReceive('delete')
            ->with(Mockery::on(static function ($arg) use ($timeEntry) {
                return $arg->is($timeEntry);
            }))
            ->andThrow(
                new UnprocessableException(
                    "Unable to delete time entry.",
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        $this->app->instance(TimeEntryService::class, $mockService);

        $response = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'Unable to delete time entry.',
            'errors' => ['params' => 'Unable to delete time entry.']
        ]);
    }

    #[Test]
    public function it_handles_concurrent_deletion_attempts(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        /** @var TimeEntry $timeEntry */
        $timeEntry = TimeEntry::factory()->create();

        $response1 = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id");
        $response2 = $this->deleteJson("$this->apiBaseUrl/$this->urlPath/$timeEntry->id");

        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response2->assertJsonFragment([
            'message' => "Resource $timeEntry->id not found.",
            'errors' => []
        ]);
    }
}
