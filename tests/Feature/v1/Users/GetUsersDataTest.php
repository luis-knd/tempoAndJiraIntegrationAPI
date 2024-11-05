<?php

namespace Tests\Feature\v1\Users;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Basic\User;
use App\Services\v1\Basic\UserService;
use Database\Seeders\v1\Basic\UserSeeder;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GetUsersDataTest extends TestCase
{
    use RefreshDatabase;

    private string $urlPath = 'users';
    private Generator $faker;

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
    public function an_authenticated_user_can_get_their_data(): void // phpcs:ignore
    {
        list($user, $authenticationResponse) = $this->loginUser();

        $response = $this->getJson(
            "$this->apiBaseUrl/$this->urlPath/" . $user->id,
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
    public function an_authenticated_user_cannot_get_their_data_from_another_user(): void // phpcs:ignore
    {
        User::factory()->count(1)->create(['email' => 'carolina@lcandesign.com']);
        $otherUser = User::where('email', 'carolina@lcandesign.com')->first();
        list($user, $authenticationResponse) = $this->loginUser();

        $response = $this->getJson(
            "$this->apiBaseUrl/$this->urlPath/" . $otherUser->id,
            ['Authorization' => "Bearer " . $authenticationResponse['data']['token']]
        );

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['message' => 'This action is unauthorized.']);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_data_from_inexistent_user(): void // phpcs:ignore
    {
        list($user, $authenticationResponse) = $this->loginUser();
        $wrongId = $this->faker->uuid();
        $response = $this->getJson(
            "$this->apiBaseUrl/$this->urlPath/" . $wrongId,
            ['Authorization' => "Bearer " . $authenticationResponse['data']['token']]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['data' => [], 'errors' => [], 'message' => "Resource $wrongId not found."]);
    }

    #[Test]
    public function an_user_not_logged_in_cannot_get_their_data(): void // phpcs:ignore
    {
        $user = User::first();
        $response = $this->getJson(
            "$this->apiBaseUrl/$this->urlPath/" . $user->id,
            ['Authorization' => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.J8r2ShjOa6L7rhzVDl3VixsLk"]
        );
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'Unauthenticated.', 'errors' => []]);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users(): void // phpcs:ignore
    {
        $this->loginUser();
        User::factory()->count(149)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");
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
    public function an_authenticated_user_can_get_all_users_and_set_items_per_page(): void // phpcs:ignore
    {
        $this->loginUser();
        User::factory()->count(149)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?page_size=50");
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
    public function an_authenticated_user_can_get_all_users_and_set_current_page(): void // phpcs:ignore
    {
        $this->loginUser();
        User::factory()->count(149)->create();

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath?page_size=50&page=2");
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

    #[Test]
    public function it_should_return_an_error_response_when_unprocessable_exception_is_thrown(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->mock(UserService::class, function ($mock) {
            $mock->shouldReceive('index')->andThrow(
                new UnprocessableException(
                    'Invalid parameters',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
        });

        $response = $this->getJson("$this->apiBaseUrl/$this->urlPath");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Invalid parameters',
            'errors' => [
                'params' => 'Invalid parameters',
            ],
        ]);
    }

    #[Test]
    public function it_should_return_the_correct_status_and_error_message(): void // phpcs:ignore
    {
        $this->loginWithFakeUser();
        $this->mock(UserService::class, function ($mock) {
            $mock->shouldReceive('index')->andThrow(
                new UnprocessableException('Custom error message', Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        });

        $response = $this->getJson("$this->apiBaseUrl/users");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Custom error message',
            'errors' => [
                'params' => 'Custom error message',
            ],
        ]);
    }
}
