<?php

declare(strict_types=1);

namespace Modules\User\Domain\Contracts;

use Ramsey\Uuid\UuidInterface;
use Modules\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    // Comandos (Write)
    public function save(User $user): void;

    public function update(User $user): void;

    public function delete(UuidInterface $id): void;

    // Queries (Read)
    public function findById(UuidInterface $id): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * Retorna todos os usuários
     *
     * @return User[]
     */
    public function findAll(): array;

    /**
     * Retorna usuários paginados
     *
     * @param int $page
     * @param int $perPage
     * @return array{data: User[], total: int, per_page: int, current_page: int, last_page: int}
     */
    public function findPaginated(
        int $page,
        int $perPage,
        ?array $searchColumns = null,
        ?string $searchTerm = null,
        ?array $sorts = null,
    ): array;

    /**
     * Retorna Usuários com cursor pagination
     *
     * @param int $limit
     * @param string|null $cursor
     * @param array<string>|null $searchColumns Colunas para busca
     * @param string|null $searchTerm Termo de busca
     * @param array<array{column: string, direction: string}>|null $sorts Ordenação
     * @return array{users: User[], next_cursor: string|null, prev_cursor: string|null}
     */
    public function findCursorPaginated(
        int $limit,
        ?string $cursor = null,
        ?array $searchColumns = null,
        ?string $searchTerm = null,
        ?array $sorts = null,
    ): array;

    /**
     * Retorna opções de usuários para dropdowns/selects
     *
     * @return array<array{id: string, name: string}>
     */
    public function findOptions(): array;
}
