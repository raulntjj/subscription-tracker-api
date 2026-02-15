<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

use Modules\Subscription\Domain\Entities\BillingHistory;

/**
 * DTO para representar dados de histórico de faturamento
 */
final readonly class BillingHistoryDTO
{
    public function __construct(
        public string $id,
        public string $subscriptionId,
        public int $amountPaid,
        public string $amountPaidFormatted,
        public string $paidAt,
        public string $createdAt,
    ) {
    }

    /**
     * Cria DTO a partir da entidade de domínio
     */
    public static function fromEntity(BillingHistory $entity, string $currency = 'BRL'): self
    {
        return new self(
            id: $entity->id()->toString(),
            subscriptionId: $entity->subscriptionId()->toString(),
            amountPaid: $entity->amountPaid(),
            amountPaidFormatted: self::formatPrice($entity->amountPaid(), $currency),
            paidAt: $entity->paidAt()->format('Y-m-d H:i:s'),
            createdAt: $entity->createdAt()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Cria DTO a partir de um registro do banco
     */
    public static function fromDatabase(object|array $data, string $currency = 'BRL'): self
    {
        $data = (array) $data;

        return new self(
            id: $data['id'],
            subscriptionId: $data['subscription_id'],
            amountPaid: (int) $data['amount_paid'],
            amountPaidFormatted: self::formatPrice((int) $data['amount_paid'], $currency),
            paidAt: $data['paid_at'],
            createdAt: $data['created_at'],
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscriptionId,
            'amount_paid' => $this->amountPaid,
            'amount_paid_formatted' => $this->amountPaidFormatted,
            'paid_at' => $this->paidAt,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Formata preço baseado na moeda
     */
    private static function formatPrice(int $priceInCents, string $currency): string
    {
        $amount = $priceInCents / 100;

        return match ($currency) {
            'BRL' => sprintf('R$ %.2f', $amount),
            'USD' => sprintf('$ %.2f', $amount),
            'EUR' => sprintf('€ %.2f', $amount),
            default => sprintf('%.2f %s', $amount, $currency),
        };
    }
}
