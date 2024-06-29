<?php

namespace App\Http\Controllers\v1\Basic;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Basic\UserRequest;
use App\Http\Resources\Basic\UserResource;
use App\Models\v1\Basic\User;
use App\Services\Basic\UserService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserController
 *
 * @package   App\Http\Controllers\v1
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        abort(Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserRequest $request The request object containing the user data.
     * @return JsonResponse The JSON response containing the created user.
     * @Request({
     *       summary: Create a new user,
     *       description: Stores a new user in the database and returns the created user,
     *       tags: Users,Create
     *   })
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = $request->validated();
        return jsonResponse(['user' => UserResource::make(
            $this->userService->make($user)
        )]);
    }

    /**
     * Display the specified resource.
     */
    public function show(): void
    {
        abort(Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UserRequest $request The request object containing the updated user data.
     * @param User        $user    The user to be updated.
     * @return JsonResponse The JSON response containing the updated user.
     * @Request({
     *       summary: Update a user,
     *       description: Updates the specified user in the database and returns the updated user,
     *       tags: Users,Update
     *   })
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        $params = $request->validated();
        $userUpdated = $this->userService->update($user, $params);
        return jsonResponse(['user' => UserResource::make($userUpdated)]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(): void
    {
        abort(Response::HTTP_NOT_IMPLEMENTED);
    }
}
