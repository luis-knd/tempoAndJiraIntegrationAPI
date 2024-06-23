<?php

namespace Tests\Feature;

use App\Models\v1\User;
use Database\Seeders\UserSeeder;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UpdateUserDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->faker = Factory::create();
    }

    #[Test]
    public function an_authenticated_user_can_modify_their_data(): void
    {
        $data = [
            'name' => 'Luis Rafael',
            'lastname' => 'Candelario Gonzalez',
        ];

        $response = $this->apiAs(user: User::first(), method: 'put', uri: "$this->apiBaseUrl/users/" . User::first()->id, data: $data);

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
                    'name' => 'Luis Rafael',
                    'lastname' => 'Candelario Gonzalez',
                ]
            ],
            'message' => 'OK',
            'errors' => [],
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis Rafael',
            'lastname' => 'Candelario Gonzalez',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function name_must_be_required(): void
    {
        $data = [
            'name' => '',
            'lastname' => 'Candelario',
        ];

        $response = $this->apiAs(user: User::first(), method: 'put', uri: "$this->apiBaseUrl/users/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['name']]);
    }

    #[Test]
    public function name_must_have_at_lease_2_characters(): void
    {
        $data = [
            'name' => 'L',
            'lastname' => 'Candelario',
        ];

        $response = $this->apiAs(user: User::first(), method: 'put', uri: "$this->apiBaseUrl/users/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['name']]);
    }

    #[Test]
    public function lastname_must_be_required(): void
    {
        $data = [
            'name' => 'Luis',
            'lastname' => '',
        ];

        $response = $this->apiAs(user: User::first(), method: 'put', uri: "$this->apiBaseUrl/users/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['lastname']]);
    }

    #[Test]
    public function lastname_must_have_at_lease_2_characters(): void
    {
        $data = [
            'name' => 'Luis',
            'lastname' => 'C',
        ];

        $response = $this->apiAs(user: User::first(), method: 'put', uri: "$this->apiBaseUrl/users/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['lastname']]);
    }

    #[Test]
    public function an_authenticated_user_cannot_modify_their_email(): void
    {
        $data = [
            'email' => 'lcandelario@modified.com',
            'name' => 'Luis Rafael',
            'lastname' => 'Candelario Gonzalez',
        ];

        $response = $this->apiAs(user: User::first(), method: 'put', uri: "$this->apiBaseUrl/users/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_OK);
        $userId = $response->json('data.user.id');

        $response->assertJsonFragment([
            'data' => [
                'user' => [
                    'id' => $userId,
                    'email' => 'lcandelario@lcandesign.com',
                    'name' => 'Luis Rafael',
                    'lastname' => 'Candelario Gonzalez',
                ]
            ],
            'message' => 'OK',
            'errors' => [],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis Rafael',
            'lastname' => 'Candelario Gonzalez',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function an_authenticated_user_cannot_modify_their_password(): void
    {
        $data = [
            'password' => 'password_modified',
            'name' => 'Luis Rafael',
            'lastname' => 'Candelario Gonzalez',
        ];
        $user = User::first();

        $response = $this->apiAs(user: $user, method: 'put', uri: "$this->apiBaseUrl/users/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors']);
        $this->assertFalse(Hash::check('password_modified', $user->password));
    }
}
