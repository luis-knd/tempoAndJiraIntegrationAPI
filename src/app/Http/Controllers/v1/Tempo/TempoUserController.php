<?php

namespace App\Http\Controllers\v1\Tempo;

use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Tempo\TempoUserRequest;
use App\Http\Resources\v1\Tempo\TempoUserCollection;
use App\Http\Resources\v1\Tempo\TempoUserResource;
use App\Models\v1\Tempo\TempoUser;
use App\Services\v1\Tempo\TempoUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Class TempoUserController
 *
 * @package   App\Http\Controllers\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TempoUserController extends Controller
{
    private TempoUserService $tempoUserService;

    public function __construct(TempoUserService $tempoUserService)
    {
        $this->tempoUserService = $tempoUserService;
    }

    /**
     * Retrieves a paginated list of TempoUser objects based on provided parameters.
     *
     * @param TempoUserRequest $request The validated request parameters for fetching TempoUsers.
     *                                  This includes query parameters for pagination, filtering, and sorting.
     * @return JsonResponse A JSON response containing the list of TempoUser objects and the total count of TempoUsers.
     *                                  Each TempoUser in the list is formatted according to the `TempoUserCollection`
     *                                  collection response format.
     * @throws UnprocessableException If the request cannot be processed due to validation errors
     *                                  or other semantic issues.
     */
    public function index(TempoUserRequest $request): JsonResponse
    {
        $params = $request->validated();
        $paginator = $this->tempoUserService->index($params);

        $tempoUsers = new TempoUserCollection($paginator);
        return jsonResponse(data: $tempoUsers);
    }

    /**
     * Stores a newly created TempoUser in the database.
     *
     * This method accepts a validated request containing the parameters for the new TempoUser object.
     * It utilizes the TempoUserService to create the TempoUser object and save it to the database.
     * The newly created TempoUser object is then returned in a JSON response using the TempoUserResource class.
     *
     * @param TempoUserRequest $request The validated request containing the parameters for the new TempoUser object.
     * @return JsonResponse A JSON response containing the newly created TempoUser object.
     */
    public function store(TempoUserRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $tempoUser = $this->tempoUserService->make($validatedData);
        return TempoUserResource::toJsonResponse($tempoUser);
    }

    /**
     * Retrieves detailed information about a specific TempoUser.
     *
     * This method accepts a validated request and a TempoUser entity, then utilizes the TempoUserService to fetch the
     * TempoUser's details. The details are serialized into JSON format using the TempoUserResource class.
     *
     * @param TempoUserRequest $request   The validated request containing parameters for fetching the
     *                                    TempoUser's details.
     * @param TempoUser        $tempoUser The TempoUser entity to be displayed.
     * @return JsonResponse A JSON response containing the detailed information of the specified TempoUser.
     * @throws UnprocessableException If the request cannot be processed due to validation errors
     *                                    or other semantic issues.
     */
    public function show(TempoUserRequest $request, TempoUser $tempoUser): JsonResponse
    {
        $params = $request->validated();
        Gate::authorize('view', $tempoUser);
        $user = $this->tempoUserService->load($tempoUser, $params);
        return TempoUserResource::toJsonResponse($user);
    }

    /**
     * Updates an existing TempoUser in the database.
     *
     * This method accepts a validated request and a TempoUser entity, then utilizes the TempoUserService to update
     * the TempoUser object and save the changes to the database. The updated TempoUser object is then returned in a
     * JSON response using the TempoUserResource class.
     *
     * @param TempoUserRequest $request   The validated request containing the parameters to update the TempoUser
     *                                    object.
     * @param TempoUser        $tempoUser The TempoUser entity to be updated.
     * @return JsonResponse A JSON response containing the updated TempoUser object.
     */
    public function update(TempoUserRequest $request, TempoUser $tempoUser): JsonResponse
    {
        Gate::authorize('update', $tempoUser);
        $params = $request->validated();
        $updatedUser = $this->tempoUserService->update($tempoUser, $params);
        return TempoUserResource::toJsonResponse($updatedUser);
    }

    public function destroy(TempoUserRequest $request, TempoUser $tempoUser): JsonResponse
    {
        $request->validated();
        Gate::authorize('delete', $tempoUser);
        $this->tempoUserService->delete($tempoUser);
        return jsonResponse(message: 'TempoUser deleted successfully.');
    }
}
