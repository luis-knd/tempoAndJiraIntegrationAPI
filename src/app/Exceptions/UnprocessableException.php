<?php

namespace App\Exceptions;

use Exception;
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
    public function render(): void
    {
        abort(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            jsonResponse(status: Response::HTTP_UNPROCESSABLE_ENTITY, message: $this->getMessage())
        );
    }
}
