<?php

namespace Tests\Feature\Auth;

use App\Models\v1\Basic\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }


    #[Test]
    public function an_authenticated_user_can_modify_their_password(): void
    {
        $data = [
            'old_password' => 'password',
            'password' => 'newPassword',
            'password_confirmation' => 'newPassword',
        ];

        $this->postJson("$this->apiBaseUrl/auth/", [
            'email' => 'lcandelario@lcandesign.com',
            'password' => 'password',
        ]);

        $response = $this->apiAs(User::first(), 'patch', "$this->apiBaseUrl/auth/password-update/" . User::first()->id, data: $data);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['message', 'data', 'errors', 'status']);
        $user = User::first();
        $this->assertTrue(Hash::check('newPassword', $user->password));
    }

    #[Test]
    public function old_password_must_be_required(): void
    {
        $data = [
            'old_password' => '',
            'password' => 'newPassword',
            'password_confirmation' => 'newPassword',
        ];

        $response = $this->apiAs(user: User::first(), method: 'patch', uri: "$this->apiBaseUrl/auth/password-update/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['old_password']]);
    }

    #[Test]
    public function old_password_must_be_correct(): void
    {
        $data = [
            'old_password' => 'wrongPassword',
            'password' => 'newPassword',
            'password_confirmation' => 'newPassword',
        ];
        $response = $this->apiAs(user: User::first(), method: 'patch', uri: "$this->apiBaseUrl/auth/password-update/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['old_password']]);
    }

    #[Test]
    public function new_password_must_be_required(): void
    {
        $data = [
            'old_password' => 'password',
            'password' => '',
            'password_confirmation' => 'newPassword',
        ];

        $response = $this->apiAs(user: User::first(), method: 'patch', uri: "$this->apiBaseUrl/auth/password-update/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }

    #[Test]
    public function new_password_must_be_confirmed(): void
    {
        $data = [
            'old_password' => 'password',
            'password' => 'newPassword',
            'password_confirmation' => '',
        ];

        $response = $this->apiAs(user: User::first(), method: 'patch', uri: "$this->apiBaseUrl/auth/password-update/" . User::first()->id, data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }
}
