<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Infrastructure\Persistence\Eloquent\UserModel;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar todos os usuários (sem paginação)
 */
final readonly class FindAllUsersQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 300; // 5 minutos

    protected function cacheTags(): array
    {
        return ['users'];
    }

    /**
     * @return array<UserDTO>
     */
    public function execute(): array
    {
        $cacheKey = "users:all";

        $this->logger()->debug('Finding all users');

        // Usa cache com remember
        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () {
                $usersData = UserModel::orderBy('created_at', 'desc')->get();

                return $usersData->map(function ($userData) {
                    return UserDTO::fromDatabase($userData);
                })->all();
            }
        );
    }
}
