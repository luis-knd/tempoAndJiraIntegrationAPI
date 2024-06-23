<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    /**
     * Logs in a user and returns a token.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request containing the user credentials.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the authentication token and expiration time.
     * @Request({
     * summary: Authenticate a user,
     * description: Validates the user's email and password, then logs them in and returns an authentication token,
     * tags: Authentication, Basics
     * })
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|min:8',
        ]);
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
}
