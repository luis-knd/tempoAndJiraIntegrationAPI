<?php

namespace App\Http\Controllers\v1\Basic;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HealthCheckController
 *
 * @package   App\Http\Controllers\v1
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @tags Basic
 */
class HealthCheckController extends Controller
{
    /**
     * Checks the health of the API.
     *
     * This endpoint verifies the application's ability to connect to the database.
     * It attempts to establish a connection and returns a success response if the connection is successful.
     * If the connection fails, it returns an error response with the relevant error message.
     *
     * @return JsonResponse A JSON response indicating the health status of the database connection.
     * If the connection is successful, the response will be a success. If the connection fails,
     * the response will include the error details.
     * @throws Exception If an error occurs while connecting to the database.
     *
     * @unauthenticated
     * @response array{
     *     "data": array{},
     *     "status": 200,
     *     "message": "OK",
     *     "errors": array{}
     * }
     */
    public function health(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            return jsonResponse();
        } catch (Exception $e) {
            return jsonResponse(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: 'ERROR',
                errors: ['error' => $e->getMessage()]
            );
        }
    }
}
