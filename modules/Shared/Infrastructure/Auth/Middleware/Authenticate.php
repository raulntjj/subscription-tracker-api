<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

final class Authenticate
{
    /**
     * Valida se o request contém um token JWT válido.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        try {
            $guard->userOrFail();
        } catch (TokenExpiredException $e) {
            return ApiResponse::unauthorized(message: 'Token expired. Please refresh or login again.');
        } catch (TokenInvalidException $e) {
            return ApiResponse::unauthorized(message: 'Invalid token.');
        } catch (JWTException $e) {
            return ApiResponse::unauthorized(message: 'Token not provided.');
        } catch (\Throwable $e) {
            return ApiResponse::unauthorized(message: 'Unauthorized.');
        }

        return $next($request);
    }
}
