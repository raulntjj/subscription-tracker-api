<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\User\Application\DTOs\UserOptionsDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

/**
 * Query para buscar opções de usuários (sem paginação)
 * Ideal para popular selects e autocompletes
 */
final readonly class FindUserOptionsQuery
{
    use Loggable;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * @return UserOptionsDTO
     */
    public function execute(?SearchDTO $search = null): UserOptionsDTO
    {
        $this->logger()->debug('Finding user options', [
            'search' => $search?->term,
        ]);

        $options = $this->userRepository->findOptions();

        return UserOptionsDTO::fromArray($options);
    }
}
