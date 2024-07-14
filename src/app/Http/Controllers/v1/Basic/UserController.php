<?php

namespace App\Http\Controllers\v1\Basic;

use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Basic\UserRequest;
use App\Http\Resources\Basic\UserCollection;
use App\Http\Resources\Basic\UserResource;
use App\Models\v1\Basic\User;
use App\Services\Basic\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Retrieves a paginated list of users.
     *
     * This endpoint allows clients to fetch a list of users according to the provided query parameters.
     * It supports pagination, filtering, and sorting through the `UserRequest` object.
     * The response includes the list of users and the total number of users.
     *
     * @param UserRequest $request The validated request parameters for fetching users.
     *                             This includes query parameters for pagination, filtering, and sorting.
     * @return JsonResponse A JSON response containing the list of users and the total count of users.
     *                             Each user in the list is formatted according to the `UserResource`
     *                             collection response format.
     * @throws UnprocessableException If the request cannot be processed due to validation errors
     *                             or other semantic issues.
     *
     * @response array{
     *     "data": array{
     *         users: array{
     *              user: array{
     *                  "id": "9c77677e-2ceb-4f21-8773-40f89cd17247",
     *                  "email": "contacto@lcandesign.com",
     *                  "name": "Luis",
     *                  "lastname": "Candelario"
     *              }
     *         }
     *     },
     *     "status": 200,
     *     "message": "OK",
     *     "errors": array{}
     * }
     */
    public function index(UserRequest $request): JsonResponse
    {
        $params = $request->validated();
        $paginator = $this->userService->index($params);

        $users = new UserCollection($paginator);
        return jsonResponse(data: $users);
    }

    /**
     * Stores a newly created user in the database.
     *
     * This endpoint handles the creation of a new user. It validates the user data provided in the request and, if
     * successful, creates a new user record in the database. The newly created user information is then returned in
     * the response.
     *
     * @param Request $request     The incoming HTTP request containing the user data to be validated and stored. This
     *                             includes fields such as 'name', 'email', and 'password'.
     * @return JsonResponse A JSON response containing the newly created user's information wrapped in a resource. The
     *                             response also includes a status code indicating the outcome of the operation.
     *
     * @response array{
     *     "data": array{
     *         user: array{
     *             "id": "9c77677e-2ceb-4f21-8773-40f89cd17247",
     *             "email": "contacto@lcandesign.com",
     *             "name": "Luis",
     *             "lastname": "Candelario"
     *         }
     *     },
     *     "status": 200,
     *     "message": "OK",
     *     "errors": array{}
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->validate(
            [
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
                'name' => 'required|min:2|max:255',
                'lastname' => 'required|min:2|max:255',
            ]
        );
        return UserResource::toJsonResponse($this->userService->make($user));
    }

    /**
     * Retrieves and displays detailed information about a specific user.
     *
     * This method accepts a validated request and a user entity, then utilizes the userService to fetch the user's
     * details. The details are serialized into JSON format using the UserResource class, enhancing the readability and
     * structure of the output.
     *
     * @param UserRequest $request The validated request containing parameters for fetching user details.
     * @param User        $user    The user entity instance to be displayed.
     * @return JsonResponse A JSON response containing the detailed information of the specified user.
     * @throws UnprocessableException
     *
     * @response array{
     *      "data": array{
     *          user: array{
     *              "id": "9c77677e-2ceb-4f21-8773-40f89cd17247",
     *              "email": "contacto@lcandesign.com",
     *              "name": "Luis",
     *              "lastname": "Candelario"
     *          }
     *      },
     *      "status": 200,
     *      "message": "OK",
     *      "errors": array{}
     *}
     */
    public function show(UserRequest $request, User $user): JsonResponse
    {
        $params = $request->validated();
        Gate::authorize('view', $user);
        return UserResource::toJsonResponse(
            $this->userService->load($user, $params)
        );
    }

    /**
     * Updates an existing user's information.
     *
     * This endpoint handles the updating of a user's details. It validates the user data provided in the request,
     * updates the specified user's information, and returns the updated user information in the response.
     *
     * @param UserRequest $request The incoming HTTP request containing the user data to be validated and updated. This
     *                             includes fields such as 'name', 'lastname'.
     * @param User        $user    The user instance to be updated, identified by its ID in the URL.
     * @return JsonResponse A JSON response containing the updated user's information wrapped in a resource. The
     *                             response also includes a status code indicating the outcome of the operation.
     *
     * @response array{
     *     "data": array{
     *         user: array{
     *              "id": "9c77677e-2ceb-4f21-8773-40f89cd17247",
     *              "email": "contacto@lcandesign.com",
     *              "name": "Luis",
     *              "lastname": "Candelario"
     *        }
     *     },
     *     "status": 200,
     *     "message": "OK",
     *     "errors": array{}
     * }
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);
        $params = $request->validated([]);
        $userUpdated = $this->userService->update($user, $params);
        return UserResource::toJsonResponse($userUpdated);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method handles the deletion of a user from the system.
     *
     * @param UserRequest $request The incoming HTTP request.
     * @param User        $user    The user instance to be deleted.
     * @return JsonResponse The JSON response indicating the result of the deletion operation.
     *
     * @response array{
     *      "data": array{
     *          user: array{},
     *      },
     *      "status": 200,
     *      "message": "User deleted successfully.",
     *      "errors": array{}
     * }
     */
    public function destroy(UserRequest $request, User $user): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $user);
        $this->userService->delete($user);
        return jsonResponse(message: 'User deleted successfully.');
    }
}
