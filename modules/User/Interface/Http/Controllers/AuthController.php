<?php

declare(strict_types=1);

namespace Modules\User\Interface\Http\Controllers;

use Throwable;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Application\DTOs\LoginDTO;
use Modules\User\Application\UseCases\LoginUseCase;
use Modules\User\Application\UseCases\LogoutUseCase;
use Modules\User\Application\UseCases\RefreshTokenUseCase;
use Modules\User\Application\Queries\GetAuthenticatedUserQuery;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final class AuthController
{
    use Loggable;

    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly LogoutUseCase $logoutUseCase,
        private readonly RefreshTokenUseCase $refreshTokenUseCase,
        private readonly GetAuthenticatedUserQuery $getAuthenticatedUserQuery,
    ) {}

    /**
     * POST /auth/login
     *
     * Autentica o usuário e retorna o token JWT.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email'    => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $dto = LoginDTO::fromArray($validated);
            $token = $this->loginUseCase->execute($dto);

            return ApiResponse::success(
                data: $token->toArray(),
                message: 'Login realizado com sucesso.'
            );
        } catch (Throwable $e) {
            $this->logger()->error('Login error', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error($e->getMessage());
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
            $this->logger()->error('Logout error', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * POST /auth/refresh
     *
     * Renova o token JWT atual.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = $this->refreshTokenUseCase->execute();

            return ApiResponse::success(
                data: $token->toArray(),
                message: 'Token renovado com sucesso.'
            );
        } catch (Throwable $e) {
            $this->logger()->error('Token refresh error', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error($e->getMessage());
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
                return ApiResponse::unauthorized('Usuário não encontrado.');
            }

            return ApiResponse::success(
                data: $user->toArray(),
                message: 'Usuário autenticado.'
            );
        } catch (Throwable $e) {
            $this->logger()->error('Get authenticated user error', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error($e->getMessage());
        }
    }
}
