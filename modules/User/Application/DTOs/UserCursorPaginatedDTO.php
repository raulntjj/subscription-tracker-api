<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserCursorPaginatedDTO
{
    /**
     * @param UserDTO[] $users
     */
    public function __construct(
        public array $users,
        public ?string $nextCursor,
        public ?string $prevCursor,
    ) {
    }

    /**
     * Cria um UserCursorPaginatedDTO a partir de dados de cursor pagination
     *
     * @param array $paginationData
     * @return self
     */
    public static function fromArray(array $paginationData): self
    {
        return new self(
            users: $paginationData['users'],
            nextCursor: $paginationData['next_cursor'],
            prevCursor: $paginationData['prev_cursor'],
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
            'users' => array_map(
                fn (UserDTO $user) => $user->toArray(),
                $this->users,
            ),
            'next_cursor' => $this->nextCursor,
            'prev_cursor' => $this->prevCursor,
        ];
    }
}
