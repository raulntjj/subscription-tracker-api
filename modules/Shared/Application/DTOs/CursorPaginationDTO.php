<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

/**
 * DTO para paginação com cursor (ideal para mobile)
 *
 * Cursor pagination é mais eficiente para grandes datasets
 * e evita problemas de duplicação quando novos itens são adicionados
 */
final readonly class CursorPaginationDTO
{
    public function __construct(
        public ?string $nextCursor,
        public ?string $prevCursor,
        public int $perPage,
        public bool $hasMore,
        public ?string $path = null,
    ) {
    }

    /**
     * Cria DTO a partir de um cursor paginator Laravel
     */
    public static function fromCursorPaginator(object $paginator): self
    {
        return new self(
            nextCursor: $paginator->nextCursor()?->encode(),
            prevCursor: $paginator->previousCursor()?->encode(),
            perPage: $paginator->perPage(),
            hasMore: $paginator->hasMorePages(),
            path: $paginator->path(),
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'next_cursor' => $this->nextCursor,
            'prev_cursor' => $this->prevCursor,
            'per_page' => $this->perPage,
            'has_more' => $this->hasMore,
            'path' => $this->path,
        ];
    }

    /**
     * Retorna URL para próxima página
     */
    public function nextUrl(): ?string
    {
        if (!$this->nextCursor || !$this->path) {
            return null;
        }

        return $this->path . '?cursor=' . $this->nextCursor;
    }

    /**
     * Retorna URL para página anterior
     */
    public function prevUrl(): ?string
    {
        if (!$this->prevCursor || !$this->path) {
            return null;
        }

        return $this->path . '?cursor=' . $this->prevCursor;
    }
}
