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
 */
class HealthCheckController extends Controller
{
    /**
     *  health
     *
     * @return \Illuminate\Http\JsonResponse
     *
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
