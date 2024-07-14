<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Basic\UserResource;
use App\Models\v1\Basic\User;
use App\Rules\Auth\CheckPasswordRule;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles authentication-related actions.
 *
 * @package   App\Http\Controllers\v1\Auth
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Authenticates a user and issues an authentication token.
     *
     * This endpoint is responsible for handling user login requests. It validates the user credentials provided in the
     * request and, if successful, returns an authentication token along with its expiration time.
     * This token can be used for subsequent requests that require authentication.
     *
     * @param Request $request      The incoming HTTP request containing the user credentials. This includes fields
     *                              such as 'email' and 'password'.
     * @return JsonResponse A JSON response containing the authentication token, its expiration time,
     *                              and any other relevant information. The response also includes a
     *                              status code indicating the outcome of the operation.
     *
     * @unauthenticated
     * @response array{
     *      "data": array{
     *          "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xL2FwaS92MS9hdXRoIiwia",
     *          "expires_at": 3600
     *      },
     *      "status": 200,
     *      "message": "OK",
     *      "errors": array{}
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|min:8',
        ]);
        return $this->authService->login();
    }

    /**
     * Updates the password for a user.
     *
     * This endpoint allows for the updating of a user's password. It requires the current password
     * and a new password to be provided in the request. The user is identified by their ID in the URL and must
     * match the authenticated user.
     *
     * @param Request $request The incoming HTTP request containing the new password information.
     * @param User    $user    The user whose password is being updated.
     * @return JsonResponse A JSON response indicating the success or failure of the password update operation.
     *
     * @response array{
     *  "data": array{
     *      user: array{
     *          "id": "9c77677e-2ceb-4f21-8773-40f89cd17247",
     *          "email": "contacto@lcandesign.com",
     *          "name": "Luis",
     *          "lastname": "Candelario"
     *      }
     *  },
     *  "status": 200,
     *  "message": "OK",
     *  "errors": array{}
     * }
     */
    public function passwordUpdate(Request $request, User $user): JsonResponse
    {
        $request->validate([
            /** @example password */
            'old_password' => ['required', 'min:8', new CheckPasswordRule()],
            /** @example new_password */
            'password' => 'required|min:8|confirmed',
        ]);
        return $this->authService->updatePassword($user);
    }

    /**
     * Initiates the process of sending a password reset link to a user.
     *
     * This method validates the incoming request to ensure it contains a valid email address that exists in the users
     * table. Upon validation, it delegates the responsibility of sending the password reset link to the AuthService.
     *
     * @param Request $request The incoming HTTP request containing the user's email address.
     * @return JsonResponse A JSON response indicating the success or failure of the password reset initiation.
     * @unauthenticated
     *
     * @response array{
     *      "data": array{},
     *      "status": 200,
     *      "message": "OK",
     *      "errors": array{}
     * }
     */
    public function sendPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        return $this->authService->sendPassword();
    }

    /**
     * Validates and initiates the password reset process.
     *
     * This method checks the validity of the incoming request data, including the presence of a valid token, email,
     * and password confirmation. Upon validation, it delegates the responsibility of performing the actual password
     * reset to the AuthService.
     *
     * @param Request $request The incoming HTTP request containing the password reset information.
     * @return JsonResponse A JSON response indicating the success or failure of the password reset initiation.
     * @unauthenticated
     *
     * @response array{
     *       "data": array{},
     *       "status": 200,
     *       "message": "OK",
     *       "errors": array{}
     * }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            /** @example eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xL2FwaS92MS9hdXRoIiw */
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);
        return $this->authService->resetPassword();
    }

    /**
     * Register a new user in the database.
     *
     * This endpoint handles the creation of a new user. It validates the user data provided in the request and, if
     * successful, creates a new user record in the database. The newly created user information is then returned in
     * the response.
     *
     * @param Request $request The incoming HTTP request containing the user data to be validated and stored. This
     *                         includes fields such as 'name', 'email', and 'password'.
     * @return JsonResponse A JSON response containing the newly created user's information wrapped in a resource. The
     *                         response also includes a status code indicating the outcome of the operation.
     * @unauthenticated
     *
     * @response array{
     * "data": array{
     * user: array{
     * "id": "9c77677e-2ceb-4f21-8773-40f89cd17247",
     * "email": "contacto@lcandesign.com",
     * "name": "Luis",
     * "lastname": "Candelario"
     * }
     * },
     * "status": 200,
     * "message": "OK",
     * "errors": array{}
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $user = $request->validate(
            [
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
                'name' => 'required|min:2|max:255',
                'lastname' => 'required|min:2|max:255',
            ]
        );
        return UserResource::toJsonResponse($this->authService->make($user));
    }
}
