<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

final class Authenticate
{
    /**
     * Valida se o request contém um token JWT válido.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            auth('api')->userOrFail();
        } catch (TokenExpiredException $e) {
            return ApiResponse::unauthorized(message: 'Token expirado. Faça refresh ou login novamente.');
        } catch (TokenInvalidException $e) {
            return ApiResponse::unauthorized(message: 'Token inválido.');
        } catch (JWTException $e) {
            return ApiResponse::unauthorized(message: 'Token não fornecido.');
        } catch (\Throwable $e) {
            return ApiResponse::unauthorized(message: 'Não autorizado.');
        }

        return $next($request);
    }
}
