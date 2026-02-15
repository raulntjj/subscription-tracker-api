<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

/**
 * DTO para retornar dados de uma configuração de webhook
 */
final readonly class WebhookConfigDTO
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $url,
        public bool $isActive,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * Cria DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            url: $data['url'],
            isActive: (bool) $data['is_active'],
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at'],
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'url' => $this->url,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
