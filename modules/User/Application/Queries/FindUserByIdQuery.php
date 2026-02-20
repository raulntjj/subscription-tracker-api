<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Ramsey\Uuid\Uuid;
use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

/**
 * Query para buscar um usuário por ID
 */
final readonly class FindUserByIdQuery
{
    use Loggable;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function execute(string $userId): ?UserDTO
    {
        $this->logger()->debug('Finding user by ID', [
            'user_id' => $userId,
        ]);

        $uuid = Uuid::fromString($userId);
        $user = $this->userRepository->findById($uuid);

        if ($user === null) {
            return null;
        }

        return UserDTO::fromEntity($user);
    }
}
