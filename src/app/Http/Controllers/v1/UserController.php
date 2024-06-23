<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CreateUserRequest;
use App\Http\Requests\v1\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\v1\User;
use Illuminate\Http\JsonResponse;

/**
 * Class UserController
 *
 * @package   App\Http\Controllers\v1
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
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
     * @Request({
     *       summary: Create a new user,
     *       description: Stores a new user in the database and returns the created user,
     *       tags: Users,Create
     *   })
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
     * @Request({
     *       summary: Update a user,
     *       description: Updates the specified user in the database and returns the updated user,
     *       tags: Users,Update
     *   })
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
