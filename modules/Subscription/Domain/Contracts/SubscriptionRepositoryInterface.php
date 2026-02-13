<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Contracts;

use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Entities\Subscription;

interface SubscriptionRepositoryInterface
{
    public function save(Subscription $entity): void;

    public function update(Subscription $entity): void;

    public function findById(UuidInterface $id): ?Subscription;

    public function delete(UuidInterface $id): void;

    /**
     * Busca todas as assinaturas ativas de um usuário
     *
     * @param string $userId UUID do usuário
     * @return array Array de objetos stdClass com dados das assinaturas
     */
    public function findActiveByUserId(string $userId): array;

    /**
     * Busca assinaturas que devem ser faturadas hoje
     *
     * @return array Array de objetos stdClass com dados das assinaturas
     */
    public function findDueForBillingToday(): array;
}
