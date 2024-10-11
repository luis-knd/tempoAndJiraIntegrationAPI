<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JsonResponseAttachment
 *
 * @package   App\Exceptions
 * @copyright 10-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JsonResponseAttachment extends Exception
{
    protected int $statusCode;
    protected array $headers;

    public function __construct($message = "", $statusCode = 400)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function render(): JsonResponse
    {
        return jsonResponse(
            status: $this->statusCode ?? Response::HTTP_BAD_REQUEST,
            message: $this->getMessage(),
            errors: ['error' => $this->getMessage()]
        );
    }
}
