<?php

namespace Tests\Feature\Auth;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    #[Test]
    public function an_existing_user_can_login(): void
    {
        $credentials = ['email' => 'lcandelario@lcandesign.com', 'password' => 'password'];

        $response = $this->post("$this->apiBaseUrl/auth/login", $credentials);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['data' => ['token']]);
    }


    #[Test]
    public function a_non_existing_user_cannot_login(): void
    {
        $credentials = ['email' => 'example@nonexisting.com', 'password' => 'password'];

        $response = $this->postJson("$this->apiBaseUrl/auth/login", $credentials);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['status' => Response::HTTP_UNAUTHORIZED, 'message' => 'Unauthorized']);
    }

    #[Test]
    public function email_must_be_required(): void
    {
        $credentials = ['password' => 'password'];

        $response = $this->postJson("$this->apiBaseUrl/auth/login", $credentials);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email field is required.']]]);
    }

    #[Test]
    public function email_must_be_valid_email(): void
    {
        $credentials = ['email' => 'adasdasasd', 'password' => 'password'];

        $response = $this->postJson("$this->apiBaseUrl/auth/login", $credentials);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email field must be a valid email address.']]]);
    }

    #[Test]
    public function email_must_be_a_string(): void
    {
        $credentials = ['email' => 123123123, 'password' => 'password'];

        $response = $this->postJson("$this->apiBaseUrl/auth/login", $credentials);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
    }

    #[Test]
    public function password_must_be_required(): void
    {
        $credentials = ['email' => 'example@nonexisting.com'];

        $response = $this->postJson("$this->apiBaseUrl/auth/login", $credentials);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }

    #[Test]
    public function password_must_have_at_lease_8_characters(): void
    {
        $credentials = ['email' => 'example@nonexisting.com', 'password' => 'abcd'];

        $response = $this->postJson("$this->apiBaseUrl/auth/login", $credentials);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }

}
