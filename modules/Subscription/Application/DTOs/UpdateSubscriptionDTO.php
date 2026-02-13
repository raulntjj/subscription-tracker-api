<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

/**
 * DTO para atualizar dados do subscription
 */
final readonly class UpdateSubscriptionDTO
{
    public function __construct(
        public ?string $name = null,
    ) {}

    /**
     * Cria DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
        );
    }

    /**
     * Converte DTO para array apenas com valores preenchidos
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
        ], fn($value) => $value !== null);
    }

    /**
     * Verifica se há algum dado para atualizar
     */
    public function hasChanges(): bool
    {
        return $this->name !== null;
    }
}
