<?php

namespace Tests\Feature\Basic;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthCheckApplicationTest extends TestCase
{
    #[Test]
    public function the_application_with_a_correct_version_return_a_successful_response(): void
    {
        $response = $this->get('/api/v1/health');
        $response->assertJson(['data' => [], 'message' => 'OK', 'errors'=> []]);
        $response->assertStatus(200);
    }

    #[Test]
    public function the_application_with_a_wrong_version_return_a_successful_response(): void
    {
        $response = $this->get('/api/v2/health');
        $response->assertStatus(302);
        $response->assertRedirect('/api/v1/health');
        $responseFollowRedirect = $this->get('/api/v1/health');
        $responseFollowRedirect->assertJson(['data' => [], 'message' => 'OK', 'errors'=> []]);
        $responseFollowRedirect->assertStatus(200);
    }

    #[Test]
    public function the_application_without_version_return_a_not_found_message(): void
    {
        $response = $this->get('/api/not-found');
        $response->assertJsonFragment(["message" => "Not Found"]);
        $response->assertStatus(404);
    }
}
