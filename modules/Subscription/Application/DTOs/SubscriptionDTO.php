<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

use Modules\Subscription\Domain\Entities\Subscription;

/**
 * DTO para representar dados de subscription nas respostas
 */
final readonly class SubscriptionDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public int $price,
        public string $priceFormatted,
        public string $currency,
        public string $billingCycle,
        public string $nextBillingDate,
        public string $category,
        public string $status,
        public string $userId,
        public string $createdAt,
        public ?string $updatedAt = null,
    ) {}

    /**
     * Cria DTO a partir da entidade de domínio
     */
    public static function fromEntity(Subscription $entity): self
    {
        return new self(
            id: $entity->id()->toString(),
            name: $entity->name(),
            price: $entity->price(),
            priceFormatted: $entity->currency()->format($entity->price()),
            currency: $entity->currency()->value,
            billingCycle: $entity->billingCycle()->value,
            nextBillingDate: $entity->nextBillingDate()->format('Y-m-d'),
            category: $entity->category(),
            status: $entity->status()->value,
            userId: $entity->userId()->toString(),
            createdAt: $entity->createdAt()->format('Y-m-d H:i:s'),
            updatedAt: $entity->updatedAt()?->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Cria DTO a partir de um registro do banco (stdClass ou array)
     */
    public static function fromDatabase(object|array $data): self
    {
        $data = (array) $data;
        
        // Formata o preço baseado na moeda
        $price = (int) $data['price'];
        $priceFormatted = self::formatPrice($price, $data['currency']);

        return new self(
            id: $data['id'],
            name: $data['name'],
            price: $price,
            priceFormatted: $priceFormatted,
            currency: $data['currency'],
            billingCycle: $data['billing_cycle'],
            nextBillingDate: $data['next_billing_date'],
            category: $data['category'],
            status: $data['status'],
            userId: $data['user_id'],
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at'] ?? null,
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'price_formatted' => $this->priceFormatted,
            'currency' => $this->currency,
            'billing_cycle' => $this->billingCycle,
            'next_billing_date' => $this->nextBillingDate,
            'category' => $this->category,
            'status' => $this->status,
            'user_id' => $this->userId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Converte DTO para formato de opções
     */
    public function toOptions(): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->name} - {$this->priceFormatted}/{$this->billingCycle}",
        ];
    }

    /**
     * Formata o preço baseado na moeda
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
