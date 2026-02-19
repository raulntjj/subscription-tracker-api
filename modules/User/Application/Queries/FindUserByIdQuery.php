<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Ramsey\Uuid\Uuid;
use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar um usuário por ID
 */
final readonly class FindUserByIdQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 3600; // 1 hora

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    protected function cacheTags(): array
    {
        return ['users'];
    }

    public function execute(string $userId): ?UserDTO
    {
        $cacheKey = "user:{$userId}";

        $this->logger()->debug('Finding user by ID', [
            'user_id' => $userId,
        ]);

        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($userId) {
                $uuid = Uuid::fromString($userId);
                $user = $this->userRepository->findById($uuid);

                if ($user === null) {
                    return null;
                }

                return UserDTO::fromEntity($user);
            }
        );
    }
}
