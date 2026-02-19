<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\User\Application\DTOs\UserOptionsDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

/**
 * Query para buscar opções de usuários (sem paginação)
 * Ideal para popular selects e autocompletes
 */
final readonly class FindUserOptionsQuery
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
     * @return UserOptionsDTO
     */
    public function execute(?SearchDTO $search = null): UserOptionsDTO
    {
        $searchKey = $search ? $search->cacheKey() : 'search:none';
        $cacheKey = "users:options:{$searchKey}";

        $this->logger()->debug('Finding user options', [
            'search' => $search?->term,
        ]);

        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () {
                $options = $this->userRepository->findOptions();

                return UserOptionsDTO::fromArray($options);
            }
        );
    }
}
