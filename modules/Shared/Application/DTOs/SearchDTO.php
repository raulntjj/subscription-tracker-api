<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

/**
 * DTO para parâmetros de busca
 *
 * Usado para transferir critérios de pesquisa entre camadas.
 * Suporta busca por termo em múltiplas colunas.
 */
final readonly class SearchDTO
{
    /**
     * @param string|null $term Termo de busca
     * @param array<string> $columns Colunas onde buscar (ex: ['name', 'email'])
     */
    public function __construct(
        public ?string $term = null,
        public array $columns = [],
    ) {
    }

    /**
     * Cria DTO a partir de Request query params
     *
     * @param array<string, mixed> $params Query params do request
     * @param array<string> $searchableColumns Colunas permitidas para busca
     */
    public static function fromRequest(array $params, array $searchableColumns = []): self
    {
        $term = isset($params['search']) && $params['search'] !== ''
            ? (string) $params['search']
            : null;

        return new self(
            term: $term,
            columns: $searchableColumns,
        );
    }

    /**
     * Verifica se há um termo de busca ativo
     */
    public function hasSearch(): bool
    {
        return $this->term !== null && $this->term !== '';
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'term' => $this->term,
            'columns' => $this->columns,
        ];
    }

    /**
     * Retorna uma chave de cache baseada nos parâmetros de busca
     */
    public function cacheKey(): string
    {
        if (!$this->hasSearch()) {
            return 'search:none';
        }

        return 'search:' . md5($this->term . ':' . implode(',', $this->columns));
    }
}
