<?php

declare(strict_types=1);

namespace Modules\User\Interface\Http\Controllers;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Application\DTOs\LoginDTO;
use Modules\User\Application\UseCases\LoginUseCase;
use Modules\User\Application\UseCases\LogoutUseCase;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use Modules\User\Application\UseCases\RefreshTokenUseCase;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\User\Application\Queries\GetAuthenticatedUserQuery;

final class AuthController
{
    use Loggable;

    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly LogoutUseCase $logoutUseCase,
        private readonly RefreshTokenUseCase $refreshTokenUseCase,
        private readonly GetAuthenticatedUserQuery $getAuthenticatedUserQuery,
    ) {
    }

    /**
     * POST /auth/login
     *
     * Autentica o usuário e retorna o token JWT.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(rules: [
                'email'    => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $dto = LoginDTO::fromArray(data: $validated);
            $token = $this->loginUseCase->execute(dto: $dto);

            return ApiResponse::success(
                data: $token->toArray(),
                message: 'Login realizado com sucesso.'
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError(errors: $e->errors());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                status: 400
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Login error', context: [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * POST /auth/logout
     *
     * Invalida o token JWT atual.
     */
    public function logout(): JsonResponse
    {
        try {
            $this->logoutUseCase->execute();

            return ApiResponse::success(
                message: 'Logout realizado com sucesso.'
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Logout error', context: [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * POST /auth/refresh
     *
     * Renova o token JWT expirado (dentro do refresh_ttl de 14 dias).
     *
     * Esta rota aceita tokens EXPIRADOS desde que estejam dentro da janela
     * de refresh (JWT_REFRESH_TTL configurado no .env).
     *
     * Se o token expirou há mais de 14 dias, retorna erro 401 e o usuário
     * precisa fazer login novamente.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = $this->refreshTokenUseCase->execute();

            return ApiResponse::success(
                data: $token->toArray(),
                message: 'Token renovado com sucesso.'
            );
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            return ApiResponse::unauthorized('Token expirou. Faça login novamente.');
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $e) {
            return ApiResponse::unauthorized('Token inválido.');
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            return ApiResponse::unauthorized('Token não fornecido.');
        } catch (\RuntimeException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        } catch (Throwable $e) {
            $this->logger()->error('Token refresh error', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /auth/me
     *
     * Retorna os dados do usuário autenticado.
     */
    public function me(): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUserQuery->execute();

            if ($user === null) {
                return ApiResponse::unauthorized(message: 'Usuário não encontrado.');
            }

            return ApiResponse::success(
                data: $user->toArray(),
                message: 'Usuário autenticado.'
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Get authenticated user error', context: [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }
}
