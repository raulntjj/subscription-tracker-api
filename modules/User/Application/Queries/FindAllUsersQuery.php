<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Application\DTOs\UserListDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

/**
 * Query para buscar todos os usuários (sem paginação)
 */
final readonly class FindAllUsersQuery
{
    use Loggable;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function execute(): UserListDTO
    {
        $this->logger()->debug('Finding all users');

        $users = $this->userRepository->findAll();

        $usersDTO = array_map(
            fn ($user) => UserDTO::fromEntity($user),
            $users
        );

        return UserListDTO::fromArray($usersDTO);
    }
}
