<?php

namespace App\Exceptions;

use Exception;
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
    public function render(): void
    {
        abort(
            Response::HTTP_BAD_REQUEST,
            jsonResponse(status: Response::HTTP_BAD_REQUEST, message: $this->getMessage())
        );
    }
}
