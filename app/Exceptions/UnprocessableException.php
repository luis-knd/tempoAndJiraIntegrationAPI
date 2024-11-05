<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UnprocessableException
 *
 * @package   App\Exceptions
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class UnprocessableException extends Exception
{
    public function render(): JsonResponse
    {
        return jsonResponse(
            status: $this->statusCode ?? Response::HTTP_UNPROCESSABLE_ENTITY,
            message: $this->getMessage(),
            errors: ['params' => $this->getMessage()]
        );
    }
}
