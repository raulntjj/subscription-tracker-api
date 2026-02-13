<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Shared\Domain\Contracts\JwtServiceInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final class LogoutUseCase
{
    use Loggable;

    public function __construct(
        private readonly JwtServiceInterface $jwtService,
    ) {
    }

    /**
     * Invalida o token JWT atual, realizando o logout.
     */
    public function execute(): void
    {
        $user = $this->jwtService->getAuthenticatedUser();

        $this->jwtService->invalidateToken();

        $this->logger()->event('user.logged_out', [
            'user_id' => $user?->id ?? 'unknown',
        ]);
    }
}
