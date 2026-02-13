<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

/**
 * DTO para criar uma configuração de webhook
 */
final readonly class CreateWebhookConfigDTO
{
    public function __construct(
        public string $url,
        public ?string $secret,
        public string $userId,
    ) {}

    /**
     * Cria DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            secret: $data['secret'] ?? null,
            userId: $data['user_id'],
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'secret' => $this->secret,
            'user_id' => $this->userId,
        ];
    }
}
