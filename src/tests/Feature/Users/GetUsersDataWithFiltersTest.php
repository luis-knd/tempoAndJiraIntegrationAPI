<?php

namespace Tests\Feature\Users;

use App\Models\v1\Basic\User;
use Database\Seeders\UserSeeder;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GetUsersDataWithFiltersTest extends TestCase
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
    public function an_authenticated_user_can_get_all_users_and_filter_by_name(): void
    {
        list($user) = $this->loginUser();
        User::factory()->count(4)->create(['name' => $this->faker->name('female')]);

        $response = $this->getJson("$this->apiBaseUrl/users?name=$user->name");

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
        $response->assertJsonFragment([
            'data' => [
                'users' => [[
                    'id' => $user->id,
                    'email' => 'lcandelario@lcandesign.com',
                    'name' => 'Luis',
                    'lastname' => 'Candelario',
                ]],
                'total' => 1,
                'count' => 1,
                'per_page' => 30,
                'current_page' => 1,
                'total_pages' => 1
            ],
            'message' => 'OK',
            'errors' => [],
        ]);
        $this->assertCount(1, $response['data']['users']);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users_and_filter_by_email(): void
    {
        list($user) = $this->loginUser();
        User::factory()->count(4)->create();

        $response = $this->getJson("$this->apiBaseUrl/users?email=$user->email");

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
        $response->assertJsonFragment([
            'data' => [
                'users' => [[
                    'id' => $user->id,
                    'email' => 'lcandelario@lcandesign.com',
                    'name' => 'Luis',
                    'lastname' => 'Candelario',
                ]],
                'total' => 1,
                'count' => 1,
                'per_page' => 30,
                'current_page' => 1,
                'total_pages' => 1
            ],
            'message' => 'OK',
            'errors' => [],
        ]);
        $this->assertCount(1, $response['data']['users']);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users_and_filter_by_inexistent_email(): void
    {
        list($user) = $this->loginUser();
        User::factory()->count(4)->create();

        $response = $this->getJson("$this->apiBaseUrl/users?email=doesnotexist$user->email");

        $response->assertJsonStructure(
            ['data' => [
                'users' => [],
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
        $response->assertJsonFragment([
            'data' => [
                'users' => [],
                'total' => 0,
                'count' => 0,
                'per_page' => 30,
                'current_page' => 1,
                'total_pages' => 1
            ],
            'message' => 'OK',
            'errors' => [],
        ]);
        $this->assertCount(0, $response['data']['users']);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users_and_order_by_name_asc(): void
    {
        $this->loginUser();
        User::factory()->count(1)->create(['name' => 'Rafucho']);
        User::factory()->count(1)->create(['name' => 'Pepeto']);
        User::factory()->count(1)->create(['name' => 'Penelope']);
        User::factory()->count(1)->create(['name' => 'Anacleta']);

        $response = $this->getJson("$this->apiBaseUrl/users?sort=name");

        $response->assertStatus(Response::HTTP_OK);
        $userNames = array_column($response->json('data.users'), 'name');
        $expectedNameOrder = ['Anacleta', 'Luis', 'Penelope', 'Pepeto', 'Rafucho'];
        $this->assertEquals($expectedNameOrder, $userNames);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users_and_order_by_name_desc(): void
    {
        $this->loginUser();
        User::factory()->count(1)->create(['name' => 'Rafucho']);
        User::factory()->count(1)->create(['name' => 'Pepeto']);
        User::factory()->count(1)->create(['name' => 'Penelope']);
        User::factory()->count(1)->create(['name' => 'Anacleta']);

        $response = $this->getJson("$this->apiBaseUrl/users?sort=-name");

        $response->assertStatus(Response::HTTP_OK);
        $userNames = array_column($response->json('data.users'), 'name');
        $expectedNameOrder = ['Rafucho', 'Pepeto', 'Penelope', 'Luis', 'Anacleta'];
        $this->assertEquals($expectedNameOrder, $userNames);
    }

    #[Test]
    public function an_authenticated_user_can_get_all_users_and_order_by_name_desc_and_lastname_asc(): void
    {
        $this->loginUser();
        User::factory()->count(1)->create(['name' => 'Luis', 'lastname' => 'Candelario Gonzalez']);
        User::factory()->count(1)->create(['name' => 'Pepeto', 'lastname' => 'Brown']);
        User::factory()->count(1)->create(['name' => 'Pepeto', 'lastname' => 'Gonzalez']);
        User::factory()->count(1)->create(['name' => 'Luis', 'lastname' => 'Jardin']);
        User::factory()->count(1)->create(['name' => 'Luis', 'lastname' => 'De Sousa']);

        $response = $this->getJson("$this->apiBaseUrl/users?sort=-name,lastname");

        $response->assertStatus(Response::HTTP_OK);
        $users = $response->json('data.users');
        $userNames = array_map(function($user) {
            return ['name' => $user['name'], 'lastname' => $user['lastname']];
        }, $users);
        $expectedNameOrder = [
            ['name' => 'Pepeto', 'lastname' => 'Brown'],
            ['name' => 'Pepeto', 'lastname' => 'Gonzalez'],
            ['name' => 'Luis', 'lastname' => 'Candelario'],
            ['name' => 'Luis', 'lastname' => 'Candelario Gonzalez'],
            ['name' => 'Luis', 'lastname' => 'De Sousa'],
            ['name' => 'Luis', 'lastname' => 'Jardin']
        ];

        $this->assertEquals($expectedNameOrder, $userNames);
    }

    #[Test]
    public function an_authenticated_user_cannot_get_all_users_order_by_an_unknown_field(): void
    {
        $this->loginUser();
        User::factory()->count(1)->create(['name' => 'Rafucho']);
        User::factory()->count(1)->create(['name' => 'Pepeto']);
        User::factory()->count(1)->create(['name' => 'Penelope']);
        User::factory()->count(1)->create(['name' => 'Anacleta']);

        $response = $this->getJson("$this->apiBaseUrl/users?sort=password");
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'data' => [],
            'message' => 'The password is present in sort param but is not available for sort',
            'errors' => [
                'sort' => ['The password is present in sort param but is not available for sort']
            ]
        ]);
    }
}
