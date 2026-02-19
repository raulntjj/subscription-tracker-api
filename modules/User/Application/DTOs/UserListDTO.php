<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserListDTO
{
    /**
     * @param UserDTO[] $users
     */
    public function __construct(
        public int $total,
        public array $users,
    ) {
    }

    /**
     * Cria um UserListDTO a partir de um array de UserDTO
     * 
     * @param UserDTO[] $users
     * @return self
     */
    public static function fromArray(array $users): self
    {
        return new self(
            total: count($users),
            users: $users,
        );
    }

    /**
     * Converte o DTO para array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'users' => array_map(
                fn(UserDTO $user) => $user->toArray(),
                $this->users
            ),
        ];
    }
}
