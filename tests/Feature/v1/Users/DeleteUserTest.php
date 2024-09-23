<?php

namespace Tests\Feature\v1\Users;

use App\Models\v1\Basic\User;
use Database\Seeders\v1\Basic\UserSeeder;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->faker = Factory::create();
    }

    #[Test]
    public function an_authenticated_user_can_delete_their_account(): void
    {
        $user = User::first();

        $response = $this->apiAs(user: $user, method: 'delete', uri: "$this->apiBaseUrl/users/" . $user->id);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => [], 'message' => 'User deleted successfully.', 'errors' => []]);
        $this->assertDatabaseHas('users', [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis',
            'lastname' => 'Candelario',
            'deleted_at' => now()->format('Y-m-d H:i:s')
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function an_authenticated_user_cannot_delete_another_users_account(): void
    {
        User::factory()->count(1)->create(['email' => 'carolina@lcandesign.com']);
        $otherUser = User::where('email', 'carolina@lcandesign.com')->first();
        $user = User::first();


        $response = $this->apiAs(user: $user, method: 'delete', uri: "$this->apiBaseUrl/users/" . $otherUser->id);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['data' => [], 'message' => 'This action is unauthorized.', 'errors' => []]);
        $this->assertDatabaseMissing('users', [
            'email' => 'carolina@lcandesign.com',
            'deleted_at' => now()->format('Y-m-d H:i:s')
        ]);
        $this->assertDatabaseCount('users', 2);
    }

    #[Test]
    public function an_authenticated_user_has_an_error_when_trying_to_delete_a_non_existing_user(): void
    {
        $user = User::first();
        $fakeUserId = $this->faker->uuid;

        $response = $this->apiAs(user: $user, method: 'delete', uri: "$this->apiBaseUrl/users/$fakeUserId");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['data' => [], 'message' => "Resource $fakeUserId not found.", 'errors' => []]);
        $this->assertDatabaseMissing('users', [
            'email' => 'lcandelario@lcandesign.com',
            'name' => 'Luis',
            'lastname' => 'Candelario',
            'deleted_at' => now()->format('Y-m-d H:i:s')
        ]);
        $this->assertDatabaseCount('users', 1);
    }

}
