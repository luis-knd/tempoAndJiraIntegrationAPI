<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates a JSON response with optional data, status, message, and errors.
 *
 * This method is designed to streamline the creation of standardized JSON responses within your application.
 * It allows for the inclusion of data, HTTP status codes, custom messages, and error arrays,
 * making it versatile for various response scenarios.
 *
 * @param mixed  $data    An associative array containing the data to be returned in the JSON response.
 *                        Default is an empty array.
 * @param int    $status  The HTTP status code for the response.
 *                        Defaults to {@link Response::HTTP_OK} (200).
 * @param string $message A custom message to include in the response. Defaults to 'OK'.
 * @param array  $errors  An array of error messages or objects. Useful for API validation errors.
 *                        Defaults to an empty array.
 * @return JsonResponse A JSON response instance with the specified data, status, message, and errors.
 *
 */
function jsonResponse(
    mixed $data = [],
    int $status = Response::HTTP_OK,
    string $message = 'OK',
    array $errors = []
): JsonResponse {
    return response()->json(compact('data', 'status', 'message', 'errors'), $status);
}
