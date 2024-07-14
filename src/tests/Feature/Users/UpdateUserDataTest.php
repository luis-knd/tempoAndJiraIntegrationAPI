<?php

namespace Tests\Feature\Users;

use App\Models\v1\Basic\User;
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

    #[Test]
    public function an_authenticated_user_cannot_modify_data_of_other_user(): void
    {
        // Create a new user
        $newUserData = [
            'email' => 'contacto@lcandesign.com',
            'password' => 'password',
            'name' => 'Contacto',
            'lastname' => 'Gonzalez',
        ];
        $createUserResponse = $this->postJson("$this->apiBaseUrl/auth/register", $newUserData);

        // Log in as a different user
        $credentials = ['email' => 'lcandelario@lcandesign.com', 'password' => 'password'];
        $this->post("$this->apiBaseUrl/auth/login", $credentials);
        // Prepare new data for updating
        $newData = [
            'password' => 'password_modified',
            'name' => 'Luis Rafael',
            'lastname' => 'Candelario Gonzalez',
        ];
        $userNew = User::all()->where('id', '=', $createUserResponse->json('data.user.id'))->first();
        $response = $this->apiAs(user: $userNew, method: 'put', uri: "$this->apiBaseUrl/users/" . $userNew->id, data: $newData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['message' => 'This action is unauthorized.']);
    }
}
