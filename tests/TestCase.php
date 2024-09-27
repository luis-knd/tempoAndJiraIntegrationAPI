<?php

namespace Tests;

use App\Models\v1\Basic\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    protected string $apiBaseUrl = '/api/v1';

    /**
     * Sends an API request as a specific user with the given method, URI, and data.
     *
     * @param User   $user   The user to send the request as.
     * @param string $method The HTTP method to use for the request.
     * @param string $uri    The URI to send the request to.
     * @param array  $data   The data to include in the request body. Default is an empty array.
     */
    protected function apiAs(User $user, string $method, string $uri, array $data = []): TestResponse
    {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($user) //@phpstan-ignore-line
        ];
        return $this->json($method, $uri, $data, $headers);
    }

    protected function loginWithFakeUser(): void
    {
        User::factory()->create(['password' => 'password']);
        $user = User::first();
        $credentials = ['email' => $user->email, 'password' => 'password'];
        $this->post("$this->apiBaseUrl/auth/login", $credentials);
    }
}
