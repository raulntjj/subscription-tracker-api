<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

/**
 * DTO para parâmetros de ordenação
 *
 * Usado para transferir critérios de ordenação entre camadas.
 * Suporta múltiplas colunas de ordenação.
 */
final readonly class SortDTO
{
    /**
     * @param array<array{column: string, direction: string}> $sorts Lista de ordenações
     */
    public function __construct(
        public array $sorts = [],
    ) {
    }

    /**
     * Cria DTO a partir de Request query params
     *
     * Formato esperado:
     * - sort_by=name&sort_direction=asc (simples)
     * - sort_by=name,email&sort_direction=asc,desc (múltiplo)
     *
     * @param array<string, mixed> $params Query params do request
     * @param array<string> $sortableColumns Colunas permitidas para ordenação
     * @param string $defaultColumn Coluna padrão de ordenação
     * @param string $defaultDirection Direção padrão de ordenação
     */
    public static function fromRequest(
        array $params,
        array $sortableColumns = [],
        string $defaultColumn = 'created_at',
        string $defaultDirection = 'desc',
    ): self {
        $sortBy = isset($params['sort_by']) && $params['sort_by'] !== ''
            ? explode(',', (string) $params['sort_by'])
            : [$defaultColumn];

        $sortDirection = isset($params['sort_direction']) && $params['sort_direction'] !== ''
            ? explode(',', (string) $params['sort_direction'])
            : [$defaultDirection];

        $sorts = [];

        foreach ($sortBy as $index => $column) {
            $column = trim($column);

            // Valida se a coluna é permitida
            if (!empty($sortableColumns) && !in_array($column, $sortableColumns, true)) {
                continue;
            }

            $direction = isset($sortDirection[$index])
                ? strtolower(trim($sortDirection[$index]))
                : $defaultDirection;

            // Valida direção
            if (!in_array($direction, ['asc', 'desc'], true)) {
                $direction = $defaultDirection;
            }

            $sorts[] = [
                'column' => $column,
                'direction' => $direction,
            ];
        }

        // Se nenhuma ordenação válida, usa padrão
        if (empty($sorts)) {
            $sorts[] = [
                'column' => $defaultColumn,
                'direction' => $defaultDirection,
            ];
        }

        return new self(sorts: $sorts);
    }

    /**
     * Verifica se há ordenações definidas
     */
    public function hasSorts(): bool
    {
        return !empty($this->sorts);
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'sorts' => $this->sorts,
        ];
    }

    /**
     * Retorna uma chave de cache baseada nos parâmetros de ordenação
     */
    public function cacheKey(): string
    {
        if (!$this->hasSorts()) {
            return 'sort:default';
        }

        $parts = array_map(
            fn (array $sort) => "{$sort['column']}:{$sort['direction']}",
            $this->sorts
        );

        return 'sort:' . implode('|', $parts);
    }
}
