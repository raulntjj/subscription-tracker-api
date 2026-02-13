<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\User\Application\DTOs\AuthTokenDTO;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final class RefreshTokenUseCase
{
    use Loggable;

    public function __construct(
        private readonly JwtServiceInterface $jwtService,
    ) {
    }

    /**
     * Renova o token JWT atual (rotação de token).
     */
    public function execute(): AuthTokenDTO
    {
        $token = $this->jwtService->refreshToken();

        $this->logger()->info('Token refreshed via use case', [
            'user_id' => auth('api')->id(),
        ]);

        return AuthTokenDTO::fromToken($token, $this->jwtService->getTokenTtl());
    }
}
