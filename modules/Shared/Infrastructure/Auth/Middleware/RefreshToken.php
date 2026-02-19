<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

final class RefreshToken
{
    /**
     * Tenta renovar automaticamente tokens expirados.
     * Adiciona o novo token no header da resposta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            auth('api')->userOrFail();
        } catch (TokenExpiredException) {
            try {
                $newToken = auth('api')->refresh();

                /** @var Response $response */
                $response = $next($request);

                return $this->addTokenToResponse($response, $newToken);
            } catch (JWTException) {
                return ApiResponse::unauthorized('Não foi possível renovar o token. Faça login novamente.');
            }
        } catch (JWTException) {
            return ApiResponse::unauthorized('Token não fornecido ou inválido.');
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
