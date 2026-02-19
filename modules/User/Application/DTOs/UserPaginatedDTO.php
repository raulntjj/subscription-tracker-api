<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserPaginatedDTO
{
    /**
     * @param UserDTO[] $data
     */
    public function __construct(
        public array $data,
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
    ) {
    }

    /**
     * Cria um UserPaginatedDTO a partir de dados de paginação
     * 
     * @param array $paginationData
     * @return self
     */
    public static function fromArray(array $paginationData): self
    {
        return new self(
            data: $paginationData['data'],
            total: $paginationData['total'],
            perPage: $paginationData['per_page'],
            currentPage: $paginationData['current_page'],
            lastPage: $paginationData['last_page'],
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
                fn(UserDTO $user) => $user->toArray(),
                $this->data
            ),
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
        ];
    }
}
