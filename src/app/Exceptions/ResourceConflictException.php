<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResourceConflictException
 *
 * @package   App\Exceptions
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class ResourceConflictException extends Exception
{
    public function render(): void
    {
        abort(
            Response::HTTP_CONFLICT,
            jsonResponse(status: Response::HTTP_CONFLICT, message: $this->getMessage())
        );
    }
}
