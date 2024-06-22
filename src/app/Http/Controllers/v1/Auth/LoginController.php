<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /**
     *  login
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
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
