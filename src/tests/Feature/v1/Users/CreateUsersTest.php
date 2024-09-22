<?php

namespace Tests\Feature\v1\Users;

use App\Models\v1\Basic\User;
use Database\Seeders\v1\Basic\UserSeeder;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CreateUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->faker = Factory::create();
    }

    #[Test]
    public function a_user_can_create_an_other_user(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'contacto@lcandesign.com',
            'password' => 'password',
            'name' => 'Rafael',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);
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
                    'email' => 'contacto@lcandesign.com',
                    'name' => 'Rafael',
                    'lastname' => 'Candelario',
                ]
            ],
            'message' => 'OK',
            'errors' => [],
        ]);
        $this->assertDatabaseHas('users', ['email' => 'contacto@lcandesign.com']);
        $this->assertDatabaseCount('users', 2);
    }

    #[Test]
    public function email_must_be_required(): void
    {
        $this->loginUser();
        $data = [
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email field is required.']]]);
    }

    #[Test]
    public function email_must_be_valid_email(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'emailSinFormato',
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email field must be a valid email address.']]]);
    }

    #[Test]
    public function email_must_be_unique(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email has already been taken.']]]);
    }

    #[Test]
    public function password_must_be_required(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }

    #[Test]
    public function password_must_have_at_lease_8_characters(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => '1234',
            'name' => 'Luis',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }

    #[Test]
    public function name_must_be_required(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'name' => '',
            'password' => 'password',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['name']]);
    }

    #[Test]
    public function name_must_have_at_lease_2_characters(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
            'name' => 'L',
            'lastname' => 'Candelario',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['name']]);
    }

    #[Test]
    public function lastname_must_be_required(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis',
            'password' => 'password',
            'lastname' => '',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['lastname']]);
    }

    #[Test]
    public function lastname_must_have_at_lease_2_characters(): void
    {
        $this->loginUser();
        $data = [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
            'name' => 'Luis',
            'lastname' => 'C',
        ];

        $response = $this->postJson("$this->apiBaseUrl/users", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['lastname']]);
    }

    /**
     *  loginUser
     *
     * @param string $email
     * @return array
     */
    public function loginUser(string $email = 'lcandelario@lcandesign.com'): array
    {
        $user = User::first();
        $credentials = ['email' => $email, 'password' => 'password'];
        $authenticationResponse = $this->post("$this->apiBaseUrl/auth/login", $credentials);
        return array($user, $authenticationResponse);
    }
}
