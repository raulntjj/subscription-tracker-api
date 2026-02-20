<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Contracts;

use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Entities\Subscription;

interface SubscriptionRepositoryInterface
{
    // Comandos (Write)
    public function save(Subscription $entity): void;

    public function update(Subscription $entity): void;

    public function delete(UuidInterface $id): void;

    // Queries (Read)
    public function findById(UuidInterface $id): ?Subscription;

    /**
     * Busca todas as assinaturas ativas de um usuário
     *
     * @param string $userId UUID do usuário
     * @return Subscription[]
     */
    public function findActiveByUserId(string $userId): array;

    /**
     * Busca assinaturas que devem ser faturadas hoje
     *
     * @return Subscription[]
     */
    public function findDueForBillingToday(): array;

    /**
     * Retorna assinaturas paginadas
     * 
     * @param int $page
     * @param int $perPage
     * @param array<string>|null $searchColumns Colunas para busca
     * @param string|null $searchTerm Termo de busca
     * @param array<array{column: string, direction: string}>|null $sorts Ordenação
     * @return array{data: Subscription[], total: int, per_page: int, current_page: int, last_page: int}
     */
    public function findPaginated(
        int $page,
        int $perPage,
        ?array $searchColumns = null,
        ?string $searchTerm = null,
        ?array $sorts = null
    ): array;

    /**
     * Retorna assinaturas com cursor pagination
     * 
     * @param int $limit
     * @param string|null $cursor
     * @param array<string>|null $searchColumns Colunas para busca
     * @param string|null $searchTerm Termo de busca
     * @param array<array{column: string, direction: string}>|null $sorts Ordenação
     * @return array{data: Subscription[], next_cursor: string|null, prev_cursor: string|null}
     */
    public function findCursorPaginated(
        int $limit,
        ?string $cursor = null,
        ?array $searchColumns = null,
        ?string $searchTerm = null,
        ?array $sorts = null
    ): array;

    /**
     * Retorna opções de assinaturas para dropdowns/selects
     * 
     * @param array<string>|null $searchColumns Colunas para busca
     * @param string|null $searchTerm Termo de busca
     * @return array<array{id: string, name: string}>
     */
    public function findOptions(?array $searchColumns = null, ?string $searchTerm = null): array;
}
