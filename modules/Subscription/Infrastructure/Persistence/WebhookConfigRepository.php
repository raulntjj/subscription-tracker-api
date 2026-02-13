<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use DateTimeImmutable;
use Modules\Subscription\Domain\Entities\WebhookConfig;
use Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\WebhookConfigModel;

final class WebhookConfigRepository extends BaseRepository implements WebhookConfigRepositoryInterface
{
    protected function getCacheTags(): array
    {
        return ['webhook_configs'];
    }

    /**
     * Salva ou atualiza configuração de webhook
     */
    public function save(WebhookConfig $webhookConfig): void
    {
        $this->upsert(
            WebhookConfigModel::class,
            ['id' => $webhookConfig->id()->toString()],
            [
                'id' => $webhookConfig->id()->toString(),
                'user_id' => $webhookConfig->userId()->toString(),
                'url' => $webhookConfig->url(),
                'secret' => $webhookConfig->secret(),
                'is_active' => $webhookConfig->isActive(),
            ]
        );
    }

    public function update(WebhookConfig $webhookConfig): void
    {
        $model = WebhookConfigModel::find($webhookConfig->id()->toString());

        if ($model === null) {
            throw new \InvalidArgumentException('Webhook config not found');
        }

        $model->url = $webhookConfig->url();
        $model->secret = $webhookConfig->secret();
        $model->is_active = $webhookConfig->isActive();

        $this->saveModel($model);
    }

    /**
     * Encontra configuração por ID
     */
    public function findById(UuidInterface $id): ?WebhookConfig
    {
        $model = WebhookConfigModel::find($id->toString());

        if($model === null) return null;

        return $this->toDomain($model);
    }

    /**
     * Encontra configuração de webhook ativa por user_id
     */
    public function findActiveByUserId(UuidInterface $userId): ?WebhookConfig
    {
        $model = WebhookConfigModel::where('user_id', $userId->toString())
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /**
     * Remove configuração (soft delete)
     */
    public function delete(UuidInterface $id): void
    {
        $model = WebhookConfigModel::find($id->toString());
    
        if ($model !== null) $this->deleteModel($model);
    }

    /**
     * Converte Model para Entity
     */
    private function toDomain(WebhookConfigModel $model): WebhookConfig
    {
        return new WebhookConfig(
            id: Uuid::fromString($model->id),
            userId: Uuid::fromString($model->user_id),
            url: $model->url,
            secret: $model->secret,
            isActive: $model->is_active,
            createdAt: new DateTimeImmutable($model->created_at->toDateTimeString()),
            updatedAt: $model->updated_at ? new DateTimeImmutable($model->updated_at->toDateTimeString()) : null
        );
    }
}
