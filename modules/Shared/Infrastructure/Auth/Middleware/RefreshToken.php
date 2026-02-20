<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

final class RefreshToken
{
    /**
     * Tenta renovar automaticamente tokens expirados.
     * Adiciona o novo token no header da resposta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        try {
            // Check if token exists and is valid
            if (!$guard->check()) {
                return ApiResponse::unauthorized(message: 'Unauthorized.');
            }
        } catch (TokenExpiredException) {
            try {
                $newToken = $guard->refresh();

                $response = $next($request);

                return $this->addTokenToResponse(response: $response, token: $newToken);
            } catch (JWTException) {
                return ApiResponse::unauthorized(message: 'Unable to refresh token. Please login again.');
            } catch (TokenInvalidException) {
                return ApiResponse::unauthorized(message: 'Invalid token.');
            }
        } catch (TokenInvalidException) {
            return ApiResponse::unauthorized(message: 'Invalid token.');
        } catch (JWTException) {
            return ApiResponse::unauthorized(message: 'Token not provided or invalid.');
        }

        return $next($request);
    }

    private function addTokenToResponse(Response $response, string $token): Response
    {
        $response->headers->set('Authorization', 'Bearer ' . $token);
        $response->headers->set('Access-Control-Expose-Headers', 'Authorization');

        return $response;
    }
}