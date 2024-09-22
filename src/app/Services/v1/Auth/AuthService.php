<?php

namespace App\Services\v1\Auth;

use App\Http\Resources\v1\Basic\UserResource;
use App\Models\v1\Basic\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthService
 *
 * @package   App\Services\Auth
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class AuthService
{
    public function login(): JsonResponse
    {
        $credentials = request(['email', 'password']);

        //@phpstan-ignore-next-line
        if (!$token = auth()->attempt($credentials)) {
            return jsonResponse(status: Response::HTTP_UNAUTHORIZED, message: 'Unauthorized');
        }

        return jsonResponse(data: [
            'token' => $token,
            'expires_in' => auth()->factory()->getTTL() * 60 //@phpstan-ignore-line
        ]);
    }

    /**
     * Creates a new User object with the provided parameters and saves it to the database.
     *
     * @param array $params An associative array containing the parameters for the new User object.
     *                      The keys should be the names of the User object's properties.
     * @return User The newly created User object.
     */
    public function make(array $params): User
    {
        $user = new User();
        $this->setParams($params, $user);
        $user->save();
        return $user;
    }

    public function updatePassword(User $user): JsonResponse
    {
        $oldPassword = request('old_password');
        $newPassword = request('password');
        if (!Hash::check($oldPassword, $user->password)) {
            return jsonResponse(status: Response::HTTP_UNAUTHORIZED, message: 'The old password is wrong');
        }
        $user->update(['password' => Hash::make($newPassword)]);
        return UserResource::toJsonResponse($user);
    }

    public function sendPassword(): JsonResponse
    {
        $email = request('email');
        $user = User::where('email', $email)->first();
        if (is_null($user)) {
            return jsonResponse(status: Response::HTTP_NOT_FOUND, message: 'User not found');
        }
        $status = Password::sendResetLink($user->only('email'));
        $sent = $status === Password::RESET_LINK_SENT;

        return jsonResponse(
            status: $sent ? Response::HTTP_OK : Response::HTTP_BAD_GATEWAY,
            message: $sent ? 'OK' : 'Link no enviado.'
        );
    }

    public function resetPassword(): JsonResponse
    {
        $status = Password::reset(
            request()->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->setRememberToken(Str::random(60));
                $user->notify(new ResetPasswordNotification($password));
            }
        );

        return match ($status) {
            Password::INVALID_TOKEN => jsonResponse(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                message: 'The token is invalid.',
                errors: ['token' => 'The token is invalid.'],
            ),
            Password::INVALID_USER => jsonResponse(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                message: 'The email is not registered.',
                errors: ['email' => 'The email is not registered.'],
            ),
            default => jsonResponse(),
        };
    }

    /**
     * A description of setting parameters for a user.
     *
     * @param array $params The parameters to set for the user
     * @param User  $user   The user object to set the parameters on
     */
    private function setParams(array $params, User $user): void
    {
        $user->name = $params['name'];
        $user->lastname = $params['lastname'];
        $user->email = $params['email'];
        $user->password = $params['password'];
    }
}
