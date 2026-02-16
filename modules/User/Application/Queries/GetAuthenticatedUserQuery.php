<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;

final class GetAuthenticatedUserQuery
{
    public function __construct(
        private readonly JwtServiceInterface $jwtService,
    ) {
    }

    /**
     * Retorna os dados do usuário autenticado.
     */
    public function execute(): ?UserDTO
    {
        $user = $this->jwtService->getAuthenticatedUser();

        if ($user === null) {
            return null;
        }

        return UserDTO::fromDatabase($user);
    }
}
