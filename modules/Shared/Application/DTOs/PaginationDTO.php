<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

/**
 * DTO base para paginação
 *
 * Usado para transferir dados de paginação entre camadas
 */
final readonly class PaginationDTO
{
    public function __construct(
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
        public ?string $nextPageUrl = null,
        public ?string $prevPageUrl = null,
    ) {
    }

    /**
     * Cria DTO a partir de um paginator Laravel
     */
    public static function fromPaginator(object $paginator): self
    {
        return new self(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
            nextPageUrl: $paginator->nextPageUrl(),
            prevPageUrl: $paginator->previousPageUrl(),
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage,
            'next_page_url' => $this->nextPageUrl,
            'prev_page_url' => $this->prevPageUrl,
        ];
    }
}
