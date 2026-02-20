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

    /**
     * Busca uma configuração de webhook por ID e usuário
     *
     * @param UuidInterface $id
     * @param UuidInterface $userId
     * @return WebhookConfig|null
     */
    public function findByIdAndUserId(UuidInterface $id, UuidInterface $userId): ?WebhookConfig;

    /**
     * Busca todas as configurações de webhook de um usuário
     *
     * @param UuidInterface $userId
     * @return WebhookConfig[]
     */
    public function findAllByUserId(UuidInterface $userId): array;
}
