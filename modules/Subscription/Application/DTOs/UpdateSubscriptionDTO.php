<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

use DateTimeImmutable;

/**
 * DTO para atualizar dados do subscription
 */
final readonly class UpdateSubscriptionDTO
{
    public function __construct(
        public string $name,
        public int $price, // Em centavos
        public string $currency,
        public string $billingCycle,
        public string $nextBillingDate,
        public string $category,
        public string $status,
    ) {
    }

    /**
     * Cria DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        $nextBillingDate = $data['next_billing_date'];
        if ($nextBillingDate instanceof \DateTimeInterface) {
            $nextBillingDate = $nextBillingDate->format('Y-m-d');
        }

        return new self(
            name: $data['name'],
            price: (int) $data['price'],
            currency: $data['currency'],
            billingCycle: $data['billing_cycle'],
            nextBillingDate: $nextBillingDate,
            category: $data['category'],
            status: $data['status'],
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_cycle' => $this->billingCycle,
            'next_billing_date' => $this->nextBillingDate,
            'category' => $this->category,
            'status' => $this->status,
        ];
    }

    /**
     * Valida se a data de próximo faturamento é futura
     */
    public function validateNextBillingDate(): bool
    {
        $nextDate = new DateTimeImmutable($this->nextBillingDate);
        $today = new DateTimeImmutable('today');

        return $nextDate >= $today;
    }
}
