<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserCursorPaginatedDTO
{
    /**
     * @param UserDTO[] $data
     */
    public function __construct(
        public array $data,
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
            data: $paginationData['data'],
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
            'data' => array_map(
                fn (UserDTO $user) => $user->toArray(),
                $this->data
            ),
            'next_cursor' => $this->nextCursor,
            'prev_cursor' => $this->prevCursor,
        ];
    }
}
