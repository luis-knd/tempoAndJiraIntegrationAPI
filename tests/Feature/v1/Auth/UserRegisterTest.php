<?php

namespace Tests\Feature\v1\Auth;

use App\Models\v1\Basic\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UserRegisterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_user_can_register(): void
    {
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);
        $response->assertStatus(Response::HTTP_OK);

        $userId = $response->json('data.user.id');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $userId
        );

        $response->assertJsonFragment([
            'data' => [
                'user' => [
                    'id' => $userId,
                    'email' => 'lcandelario@lcandesign.com',
                    'name' => 'Luis',
                    'lastname' => 'Candelario',
                ]
            ],
            'message' => 'OK',
            'errors' => [],
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function email_must_be_required(): void
    {

        $data = [
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register/", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email field is required.']]]);
    }

    #[Test]
    public function email_must_be_valid_email(): void
    {
        $data = [
            'email' => 'emailSinFormato',
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email field must be a valid email address.']]]);
    }

    #[Test]
    public function email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'lcandelario@lcandesign.com']);
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email has already been taken.']]]);
    }

    #[Test]
    public function password_must_be_required(): void
    {
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }

    #[Test]
    public function password_must_have_at_lease_8_characters(): void
    {
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => '1234',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }

    #[Test]
    public function name_must_be_required(): void
    {
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'name' => '',
            'password' => 'password',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['name']]);
    }

    #[Test]
    public function name_must_have_at_lease_2_characters(): void
    {
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
            'name' => 'L',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['name']]);
    }

    #[Test]
    public function lastname_must_be_required(): void
    {
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis',
            'password' => 'password',
            'lastname' => '',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['lastname']]);
    }

    #[Test]
    public function lastname_must_have_at_lease_2_characters(): void
    {
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'C',
        ];

        $response = $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['lastname']]);
    }

    #[Test]
    public function a_registered_user_can_login(): void
    {
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $this->postJson("$this->apiBaseUrl/auth/register", $data);

        $response = $this->postJson("$this->apiBaseUrl/auth/login", [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['data' => ['token']]);
    }
}
