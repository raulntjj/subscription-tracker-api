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
        public ?bool $isActive = null,
    ) {
    }

    /**
     * Cria DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        // Trata string vazia como null para secret
        $secret = $data['secret'] ?? null;
        if ($secret === '') {
            $secret = null;
        }

        return new self(
            id: $data['id'],
            url: $data['url'] ?? null,
            secret: $secret,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
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

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        return $data;
    }
}
