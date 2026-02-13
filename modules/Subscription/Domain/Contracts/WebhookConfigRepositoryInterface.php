<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Contracts;

use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Entities\WebhookConfig;

interface WebhookConfigRepositoryInterface
{
    public function save(WebhookConfig $entity): void;

    public function findById(UuidInterface $id): ?WebhookConfig;

    public function delete(UuidInterface $id): void;

    /**
     * Busca configurações de webhook ativas de um usuário
     *
     * @param UuidInterface $userId UUID do usuário
     * @return WebhookConfig|null
     */
    public function findActiveByUserId(UuidInterface $userId): ?WebhookConfig;
}
