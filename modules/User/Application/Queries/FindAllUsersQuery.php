<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Application\DTOs\UserListDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

/**
 * Query para buscar todos os usuários (sem paginação)
 */
final readonly class FindAllUsersQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 300; // 5 minutos

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    protected function cacheTags(): array
    {
        return ['users'];
    }

    /**
     * @return UserListDTO
     */
    public function execute(): UserListDTO
    {
        $cacheKey = "users:all";

        $this->logger()->debug('Finding all users');

        // Usa cache com remember
        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () {
                $users = $this->userRepository->findAll();

                $usersDTO = array_map(
                    fn ($user) => UserDTO::fromEntity($user),
                    $users
                );

                return UserListDTO::fromArray($usersDTO);
            }
        );
    }
}
