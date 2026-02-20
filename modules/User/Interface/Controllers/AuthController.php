<?php

declare(strict_types=1);

namespace Modules\User\Interface\Controllers;

use Throwable;
use RuntimeException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Modules\User\Application\DTOs\LoginDTO;
use Illuminate\Validation\ValidationException;
use Modules\User\Application\DTOs\CreateUserDTO;
use Modules\User\Application\UseCases\LoginUseCase;
use Modules\User\Application\UseCases\LogoutUseCase;
use Modules\User\Application\UseCases\RegisterUseCase;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Modules\Shared\Interface\Responses\ApiResponse;
use Modules\User\Application\UseCases\RefreshTokenUseCase;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\User\Application\Queries\GetAuthenticatedUserQuery;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

final class AuthController
{
    use Loggable;

    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly LogoutUseCase $logoutUseCase,
        private readonly RegisterUseCase $registerUseCase,
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
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $dto = LoginDTO::fromArray(data: $validated);
            $token = $this->loginUseCase->execute(dto: $dto);

            return ApiResponse::success(
                data: $token->toArray(),
                message: __('User::message.login_success'),
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError(errors: $e->errors());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                status: 400,
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Login error', context: [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * POST /auth/register
     *
     * Registra um novo usuário e retorna o token JWT.
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(rules: [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'surname' => ['nullable', 'string', 'max:255'],
                'profile_path' => ['nullable', 'string', 'max:500'],
            ]);

            $dto = CreateUserDTO::fromArray(data: $validated);
            $token = $this->registerUseCase->execute(dto: $dto);

            return ApiResponse::success(
                data: $token->toArray(),
                message: __('User::message.register_success'),
                status: 201,
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError(errors: $e->errors());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                status: 400,
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Register error', context: [
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
                message: __('User::message.logout_success'),
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
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = $this->refreshTokenUseCase->execute();

            return ApiResponse::success(
                data: $token->toArray(),
                message: __('User::message.token_refreshed'),
            );
        } catch (TokenExpiredException $e) {
            return ApiResponse::unauthorized(message: __('User::message.token_expired'));
        } catch (TokenInvalidException $e) {
            return ApiResponse::unauthorized(message: __('User::message.token_invalid'));
        } catch (JWTException $e) {
            return ApiResponse::unauthorized(message: __('User::message.token_not_provided'));
        } catch (RuntimeException $e) {
            return ApiResponse::unauthorized(message: $e->getMessage());
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Token refresh error', context: [
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
                return ApiResponse::unauthorized(message: __('User::message.user_not_found'));
            }

            return ApiResponse::success(
                data: $user->toArray(),
                message: __('User::message.user_retrieved_success'),
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Get authenticated user error', context: [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }
}
