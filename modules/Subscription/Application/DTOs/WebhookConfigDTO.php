<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

use Modules\Subscription\Domain\Entities\WebhookConfig;

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
        public string $platform,
        public ?string $botName,
        public ?string $serverName,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * Cria DTO a partir da entidade de domínio
     */
    public static function fromEntity(WebhookConfig $entity): self
    {
        return new self(
            id: $entity->id()->toString(),
            userId: $entity->userId()->toString(),
            url: $entity->url()->value(),
            isActive: $entity->isActive(),
            platform: $entity->platform()->value,
            botName: $entity->botName(),
            serverName: $entity->serverName(),
            createdAt: $entity->createdAt()->format('Y-m-d H:i:s'),
            updatedAt: $entity->updatedAt()?->format('Y-m-d H:i:s') ?? $entity->createdAt()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Cria DTO a partir de um registro do banco (stdClass ou array)
     */
    public static function fromDatabase(object $data): self
    {
        return new self(
            id: $data->id,
            userId: $data->user_id,
            url: $data->url,
            isActive: (bool) $data->is_active,
            platform: $data->platform ?? 'other',
            botName: $data->bot_name ?? null,
            serverName: $data->server_name ?? null,
            createdAt: $data->created_at->format('Y-m-d H:i:s'),
            updatedAt: $data->updated_at?->format('Y-m-d H:i:s') ?? $data->created_at->format('Y-m-d H:i:s'),
        );
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
            platform: $data['platform'] ?? 'other',
            botName: $data['bot_name'] ?? null,
            serverName: $data['server_name'] ?? null,
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
            'platform' => $this->platform,
            'bot_name' => $this->botName,
            'server_name' => $this->serverName,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
