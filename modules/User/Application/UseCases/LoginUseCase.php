<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Throwable;
use InvalidArgumentException;
use Modules\User\Application\DTOs\LoginDTO;
use Modules\User\Application\DTOs\AuthTokenDTO;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final class LoginUseCase
{
    use Loggable;

    public function __construct(
        private readonly JwtServiceInterface $jwtService,
    ) {
    }

    /**
     * Autentica o usuário e retorna o token JWT.
     *
     * @throws InvalidArgumentException Se as credenciais forem inválidas
     */
    public function execute(LoginDTO $dto): AuthTokenDTO
    {
        try {
            $token = $this->jwtService->attemptLogin($dto->toCredentials());

            if ($token === null) {
                $this->logger()->warning('Login failed: invalid credentials', [
                    'email' => $dto->email,
                ]);

                throw new InvalidArgumentException('Invalid credentials.');
            }

            $this->logger()->event('user.logged_in', [
                'email' => $dto->email,
            ]);

            return AuthTokenDTO::fromToken($token, $this->jwtService->getTokenTtl());
        } catch (Throwable $e) {
            $this->logger()->error('Login error', [
                'error' => $e->getMessage(),
                'email' => $dto->email,
            ]);

            throw $e;
        }
    }
}
