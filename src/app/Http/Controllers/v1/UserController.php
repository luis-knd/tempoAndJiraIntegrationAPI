<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CreateUserRequest;
use App\Http\Requests\v1\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\v1\User;
use Faker\Core\Uuid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateUserRequest $request The request object containing the user data.
     * @return JsonResponse The JSON response containing the created user.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = User::create($request->all());
        return jsonResponse(['user' => UserResource::make($user)]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request The request object containing the updated user data.
     * @return JsonResponse The JSON response containing the updated user.
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        auth()->user()->update($request->validated());
        $user = UserResource::make(auth()->user()->fresh());
        return jsonResponse(compact('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
