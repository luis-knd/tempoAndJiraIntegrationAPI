<?php

namespace App\Http\Controllers\v1;

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
 */
class HealthCheckController extends Controller
{
    /**
     * * This method checks the health of the application by attempting to connect to the database.
     * If the connection is successful, it returns a JSON response with the status 'OK'.
     * If an error occurs, it returns a JSON response with the status 'ERROR' and the error message.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response with the health status.
     * @throws \Exception If an error occurs while connecting to the database.
     * @Request({
     *      summary: Check application health,
     *      description: Attempts to connect to the database and returns the health status,
     *      tags: Basics,HealthCheck
     *  })
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
