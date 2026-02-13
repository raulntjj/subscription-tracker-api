<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

/**
 * DTO para atualizar uma configuração de webhook
 */
final readonly class UpdateWebhookConfigDTO
{
    public function __construct(
        public string $id,
        public ?string $url = null,
        public ?string $secret = null,
    ) {}

    /**
     * Cria DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            url: $data['url'] ?? null,
            secret: $data['secret'] ?? null,
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        $data = ['id' => $this->id];

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }

        if ($this->secret !== null) {
            $data['secret'] = $this->secret;
        }

        return $data;
    }
}
