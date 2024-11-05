<?php

namespace Tests\Feature\v1\Auth;

use App\Models\v1\Basic\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\v1\Basic\UserSeeder;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;
    protected string $email;
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->faker = Factory::create();
    }

    #[Test]
    public function an_authenticated_user_can_reset_their_password(): void
    {
        $this->sendNotificationToResetPassword();
        $user = User::first();

        $response = $this->ApiAs($user, 'patch',
            "$this->apiBaseUrl/auth/reset-password?token=$this->token",
            [
                'email' => $this->email,
                'password' => 'newPassword',
                'password_confirmation' => 'newPassword'
            ]
        );
        $user->refresh();

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['message' => 'OK']);
        $this->assertTrue(Hash::check('newPassword', $user->password));
    }

    #[Test]
    public function email_must_be_required(): void
    {
        $data = ['email' => ''];
        $user = User::first();

        $response = $this->apiAs($user, 'post', "$this->apiBaseUrl/auth/reset-password", data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email field is required.']]]);
    }

    #[Test]
    public function email_must_be_valid_email(): void
    {
        $data = ['email' => 'wrongEmail@'];
        $user = User::first();

        $response = $this->apiAs($user, 'post', "$this->apiBaseUrl/auth/reset-password", data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The email field must be a valid email address.']]]);
    }

    #[Test]
    public function email_must_be_an_existing_email(): void
    {
        $data = ['email' => 'lcandelario@noExiste.com'];
        $user = User::first();

        $response = $this->apiAs($user, 'post', "$this->apiBaseUrl/auth/reset-password", data: $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The selected email is invalid.']]]);
    }

    #[Test]
    public function password_must_be_required(): void
    {
        $user = User::first();
        $fakeToken = $this->faker->regexify('[A-Za-z0-9]{60}');
        $response = $this->ApiAs($user, 'patch',
            "$this->apiBaseUrl/auth/reset-password?token=$fakeToken",
            [
                'email' => $this->faker->email(),
                'password' => '',
                'password_confirmation' => 'newPassword'
            ]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
    }

    #[Test]
    public function password_confirmation_must_be_required(): void
    {
        $this->sendNotificationToResetPassword();
        $user = User::first();
        $response = $this->ApiAs($user, 'patch',
            "$this->apiBaseUrl/auth/reset-password?token=$this->token",
            [
                'email' => $this->email,
                'password' => 'newPassword',
                'password_confirmation' => ''
            ]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['password']]);
        $response->assertJsonFragment(['errors' => ['password' => ['The password field confirmation does not match.']]]);
    }


    #[Test]
    public function token_must_be_a_valid_token(): void
    {
        $this->sendNotificationToResetPassword();
        $user = User::first();
        $response = $this->ApiAs($user, 'patch',
            "$this->apiBaseUrl/auth/reset-password?token=isWrong$this->token",
            [
                'email' => $this->email,
                'password' => 'newPassword',
                'password_confirmation' => 'newPassword'
            ]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['token']]);
        $response->assertJsonFragment(['message' => 'The token is invalid.']);
        $response->assertJsonFragment(['errors' => ['token' => 'The token is invalid.']]);
    }

    #[Test]
    public function email_must_be_associated_with_the_token(): void
    {
        $this->sendNotificationToResetPassword();
        $user = User::first();
        $response = $this->ApiAs($user, 'patch',
            "$this->apiBaseUrl/auth/reset-password?token=$this->token",
            [
                'email' => "modifiedEmail@lcandesign.com",
                'password' => 'newPassword',
                'password_confirmation' => 'newPassword'
            ]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['message', 'data', 'status', 'errors' => ['email']]);
        $response->assertJsonFragment(['errors' => ['email' => ['The selected email is invalid.']]]);
    }

    #[Test]
    public function it_sends_reset_password_email_with_correct_content(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $url = 'https://lcandesign.com/auth/reset-password?token=8adfAEdsfsdfdsfdsfsdfsdf45sfsdsdf5sdfs645sdf6sdfgjhgn';
        $notification = new ResetPasswordNotification($url);
        $user->notify($notification);

        Notification::assertSentTo(
            [$user], ResetPasswordNotification::class,
            function ($notification) use ($url) {
                $mailData = $notification->toMail(null)->toArray();

                $this->assertEquals('Hello!', $mailData['introLines'][0]);
                $this->assertEquals('You are receiving this email because we received a password reset request for your account.', $mailData['introLines'][1]);
                $this->assertEquals('If you did not make this request, please ignore this email.', $mailData['introLines'][2]);
                $this->assertEquals('Notification Action', $mailData['actionText']);
                $this->assertEquals($url, $mailData['actionUrl']);
                $this->assertEquals('Thank you for using ' . getenv('APP_NAME') . '!', $mailData['outroLines'][0]);

                return true;
            }
        );
    }

    /**
     *  sendNotificationToResetPassword
     *
     *  Tests the functionality of sending a notification for resetting a password.
     *
     * @return void
     */
    public function sendNotificationToResetPassword(): void
    {
        Notification::fake();
        $data = ['email' => 'lcandelario@lcandesign.com'];
        $user = User::first();

        $response = $this->apiAs($user, 'post', "$this->apiBaseUrl/auth/reset-password", data: $data);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['message' => 'OK']);
        Notification::assertSentTo(
            [$user],
            function (ResetPasswordNotification $notification) {
                $url = $notification->url;
                $parts = parse_url($url);
                parse_str($parts['query'], $query);
                $this->token = $query['token'] ?? null;
                $this->email = $query['email'] ?? null;
                return str_contains($url, '/auth/reset-password?token=');
            }
        );
    }

}
