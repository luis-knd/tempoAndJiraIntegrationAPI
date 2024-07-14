<?php

namespace Tests\Feature\Users;

use App\Models\v1\Basic\User;
use Database\Seeders\UserSeeder;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GetUsersDataTest extends TestCase
{
    use RefreshDatabase;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->faker = Factory::create();
    }

    #[Test]
    public function an_authenticated_user_can_get_their_data(): void
    {
        list($user, $authenticationResponse) = $this->loginUser();

        $response = $this->getJson(
            "$this->apiBaseUrl/users/" . $user->id,
            ['Authorization' => "Bearer " . $authenticationResponse['data']['token']]
        );

        $response->assertJsonFragment([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => 'lcandelario@lcandesign.com',
                    'name' => 'Luis',
                    'lastname' => 'Candelario',
                ]
            ],
            'message' => 'OK',
            'errors' => [],
        ]);
    }


    #[Test]
    public function an_authenticated_user_cannot_get_their_data_from_another_user(): void
    {
        User::factory()->count(1)->create(['email' => 'carolina@lcandesign.com']);
        $otherUser = User::where('email', 'carolina@lcandesign.com')->first();
        list($user, $authenticationResponse) = $this->loginUser();

        $response = $this->getJson(
            "$this->apiBaseUrl/users/" . $otherUser->id,
            ['Authorization' => "Bearer " . $authenticationResponse['data']['token']]
        );

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['message' => 'This action is unauthorized.']);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_data_from_inexistent_user(): void
    {
        list($user, $authenticationResponse) = $this->loginUser();
        $wrongId = $this->faker->uuid();
        $response = $this->getJson(
            "$this->apiBaseUrl/users/" . $wrongId,
            ['Authorization' => "Bearer " . $authenticationResponse['data']['token']]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['data' => [], 'errors' => [], 'message' => "Resource $wrongId not found."]);
    }

    #[Test]
    public function an_user_not_logged_in_cannot_get_their_data(): void
    {
        $user = User::first();
        $response = $this->getJson(
            "$this->apiBaseUrl/users/" . $user->id,
            ['Authorization' => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.J8r2ShjOa6L7rhzVDl3VixsLk"]
        );
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users(): void
    {
        $this->loginUser();
        User::factory()->count(149)->create();

        $response = $this->getJson("$this->apiBaseUrl/users");
        $response->assertJsonStructure(
            ['data' => [
                'users' => [
                    ['id', 'email', 'name', 'lastname']
                ],
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages'
            ],
                'message',
                'errors'
            ]
        );
        $response->assertJsonPath('data.total', 150);
        $response->assertJsonPath('data.count', 30);
        $response->assertJsonPath('data.per_page', 30);
        $response->assertJsonPath('data.current_page', 1);
        $response->assertJsonPath('data.total_pages', 5);
        $this->assertCount(30, $response['data']['users']);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users_and_set_items_per_page(): void
    {
        $this->loginUser();
        User::factory()->count(149)->create();

        $response = $this->getJson("$this->apiBaseUrl/users?page_size=50");
        $response->assertJsonStructure(
            ['data' => [
                'users' => [
                    ['id', 'email', 'name', 'lastname']
                ],
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages'
            ],
                'message',
                'errors'
            ]
        );
        $response->assertJsonPath('data.total', 150);
        $response->assertJsonPath('data.count', 50);
        $response->assertJsonPath('data.per_page', 50);
        $response->assertJsonPath('data.current_page', 1);
        $response->assertJsonPath('data.total_pages', 3);
        $this->assertCount(50, $response['data']['users']);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users_and_set_current_page(): void
    {
        $this->loginUser();
        User::factory()->count(149)->create();

        $response = $this->getJson("$this->apiBaseUrl/users?page_size=50&page=2");
        $response->assertJsonStructure(
            ['data' => [
                'users' => [
                    ['id', 'email', 'name', 'lastname']
                ],
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages'
            ],
                'message',
                'errors'
            ]
        );
        $response->assertJsonPath('data.total', 150);
        $response->assertJsonPath('data.count', 50);
        $response->assertJsonPath('data.per_page', 50);
        $response->assertJsonPath('data.current_page', 2);
        $response->assertJsonPath('data.total_pages', 3);
        $this->assertCount(50, $response['data']['users']);
    }
}
