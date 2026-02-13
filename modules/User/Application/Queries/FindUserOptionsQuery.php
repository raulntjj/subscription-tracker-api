<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Infrastructure\Persistence\Eloquent\UserModel;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar opções de usuários (sem paginação)
 * Ideal para popular selects e autocompletes
 * 
 * CQRS: Queries podem usar Eloquent Models diretamente para leitura
 * Benefícios: soft deletes automático, casts, scopes, relations
 */
final readonly class FindUserOptionsQuery
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
    public function execute(?SearchDTO $search = null): array
    {
        $startTime = microtime(true);
        $searchKey = $search ? $search->cacheKey() : 'search:none';
        $cacheKey = "users:options:{$searchKey}";

        $this->logger()->debug('Finding user options', [
            'search' => $search?->term,
        ]);

        $users = $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($search, $startTime) {
                $this->logger()->debug('Cache miss - fetching from database');

                // Usa Eloquent Model - soft deletes automático!
                $query = UserModel::select(['id', 'name', 'surname', 'email', 'created_at', 'updated_at'])
                    ->orderBy('name', 'asc');

                // Aplica busca
                if ($search !== null && $search->hasSearch()) {
                    $query->where(function ($q) use ($search) {
                        foreach ($search->columns as $column) {
                            $q->orWhere($column, 'LIKE', "%{$search->term}%");
                        }
                    });
                }

                $usersData = $query->get();

                $users = $usersData->map(function ($userData) {
                    return UserDTO::fromDatabase($userData);
                })->all();

                $duration = microtime(true) - $startTime;

                $this->logger()->info('User options retrieved from database', [
                    'total' => count($users),
                    'search' => $search?->term,
                    'cache_hit' => false,
                    'duration_ms' => round($duration * 1000, 2),
                ]);

                return $users;
            }
        );

        $duration = microtime(true) - $startTime;

        $this->logger()->info('User options returned', [
            'total' => count($users),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return $users;
    }
}
