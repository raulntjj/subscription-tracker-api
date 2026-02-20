<?php

declare(strict_types=1);

namespace Modules\Shared\Interface\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    /**
     * Resposta de sucesso genérica
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Resposta de erro genérica
     */
    public static function error(
        ?string $message = 'An error occurred',
        mixed $errors = null,
        ?\Throwable $exception = null,
        int $status = Response::HTTP_BAD_REQUEST,
    ): JsonResponse {
        if ($exception !== null) {
            $message = $exception->getMessage();
        }

        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];

        if (config('app.debug')) {
            $payload['details'] = [
                'exception' => $exception ? [
                    'type' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ] : null,
                'debug_backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                'request' => [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'request_time' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
                ],
                'server' => [
                    'os' => PHP_OS,
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                ],
                'benchmark' => [
                    'memory_peak_usage' => memory_get_peak_usage(),
                    'memory_limit' => ini_get('memory_limit'),
                    'memory_usage' => memory_get_usage(),
                    'execution_time' => defined('LARAVEL_START') ? microtime(true) - LARAVEL_START : null,
                ],
                'application' => [
                    'environment' => app()->environment(),
                    'php_version' => PHP_VERSION,
                ],
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return response()->json(data: $payload, status: $status);
    }

    /**
     * Resposta de criação bem-sucedida (201 Created)
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully',
    ): JsonResponse {
        return self::success(data: $data, message: $message, status: Response::HTTP_CREATED);
    }

    /**
     * Resposta de não encontrado (404 Not Found)
     */
    public static function notFound(
        string $message = 'Resource not found',
    ): JsonResponse {
        return self::error(message: $message, errors: null, status: Response::HTTP_NOT_FOUND);
    }

    /**
     * Resposta de validação falhou (422 Unprocessable Entity)
     */
    public static function validationError(
        mixed $errors,
        string $message = 'Validation failed',
    ): JsonResponse {
        return self::error(message: $message, errors: $errors, status: Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Resposta de não autorizado (401 Unauthorized)
     */
    public static function unauthorized(
        string $message = 'Unauthorized',
    ): JsonResponse {
        return self::error(message: $message, status: Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Resposta de proibido (403 Forbidden)
     */
    public static function forbidden(
        string $message = 'Forbidden',
    ): JsonResponse {
        return self::error(message: $message, status: Response::HTTP_FORBIDDEN);
    }

    /**
     * Resposta de conflito (409 Conflict)
     */
    public static function conflict(
        string $message = 'Conflict',
        mixed $errors = null,
    ): JsonResponse {
        return self::error(message: $message, errors: $errors, status: Response::HTTP_CONFLICT);
    }

    /**
     * Resposta de erro interno do servidor (500 Internal Server Error)
     */
    public static function serverError(
        string $message = 'Internal server error',
    ): JsonResponse {
        return self::error(message: $message, status: Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Resposta sem conteúdo (204 No Content)
     */
    public static function noContent(): Response
    {
        return response()->noContent(status: 204);
    }

    /**
     * Resposta paginada
     */
    public static function paginated(
        array $items,
        int $total,
        int $perPage,
        int $currentPage,
        string $message = 'Data retrieved successfully',
    ): JsonResponse {
        return self::success([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => (int) ceil($total / $perPage),
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($currentPage * $perPage, $total),
            ],
        ], $message);
    }
}
