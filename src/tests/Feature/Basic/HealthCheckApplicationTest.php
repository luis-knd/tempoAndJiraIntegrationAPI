<?php

namespace Tests\Feature\Basic;

use Exception;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class HealthCheckApplicationTest extends TestCase
{
    #[Test]
    public function the_application_with_a_correct_version_return_a_successful_response(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertJson(['data' => [], 'message' => 'OK', 'errors' => []]);
        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function the_application_with_a_wrong_version_return_a_successful_response(): void
    {
        $response = $this->getJson('/api/v2/health');
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect('/api/v1/health');
        $responseFollowRedirect = $this->getJson('/api/v1/health');
        $responseFollowRedirect->assertJson(['data' => [], 'message' => 'OK', 'errors' => []]);
        $responseFollowRedirect->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function the_application_without_version_return_a_not_found_message(): void
    {
        $response = $this->getJson('/api/not-found');
        $response->assertJsonFragment(["message" => "Not Found"]);
        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function the_application_health_endpoint_returns_error_response_if_database_connection_fails(): void
    {
        DB::shouldReceive('connection')->andThrow(new Exception('Database connection failed.'));

        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->assertJsonFragment(['message' => 'ERROR', 'errors' => ['error' => 'Database connection failed.']]);
    }
}
