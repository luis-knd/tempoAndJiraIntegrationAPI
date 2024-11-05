<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BadRequestException
 *
 * @package   App\Exceptions
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class BadRequestException extends Exception
{
    public function render(): JsonResponse
    {
        return jsonResponse(
            status: $this->statusCode ?? Response::HTTP_BAD_REQUEST,
            message: $this->getMessage(),
            errors: ['error' => $this->getMessage()]
        );
    }
}
