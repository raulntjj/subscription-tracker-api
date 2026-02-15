<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Infrastructure\Persistence\Eloquent\UserModel;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar um usuário por ID
 *
 * CQRS: Queries podem usar Eloquent Models diretamente para leitura
 * Benefícios: soft deletes automático, casts, scopes, relations
 */
final readonly class FindUserByIdQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 3600; // 1 hora

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
                $userData = UserModel::where('id', $userId)->first();

                if ($userData == null) {
                    return null;
                }

                return UserDTO::fromDatabase($userData);
            }
        );
    }
}
