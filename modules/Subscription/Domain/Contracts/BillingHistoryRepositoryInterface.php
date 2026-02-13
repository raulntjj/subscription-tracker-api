<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Contracts;

use Modules\Subscription\Domain\Entities\BillingHistory;
use Ramsey\Uuid\UuidInterface;

interface BillingHistoryRepositoryInterface
{
    /**
     * Salva um novo histórico de faturamento
     *
     * @param BillingHistory $entity
     * @return void
     */
    public function save(BillingHistory $entity): void;

    /**
     * Busca um histórico de faturamento pelo ID
     *
     * @param UuidInterface $id
     * @return BillingHistory|null
     */
    public function findById(UuidInterface $id): ?BillingHistory;

    /**
     * Busca todos os históricos de uma assinatura
     *
     * @param string $subscriptionId UUID da assinatura
     * @return array Array de objetos stdClass
     */
    public function findBySubscriptionId(string $subscriptionId): array;

    /**
     * Calcula o total pago em um período para um usuário
     *
     * @param string $userId UUID do usuário
     * @param string $startDate Data inicial (Y-m-d)
     * @param string $endDate Data final (Y-m-d)
     * @return int Total em centavos
     */
    public function getTotalPaidInPeriod(string $userId, string $startDate, string $endDate): int;
}
