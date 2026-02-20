<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Entities\WebhookConfig;
use Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\WebhookConfigModel;

final class WebhookConfigRepository extends BaseRepository implements WebhookConfigRepositoryInterface
{
    private const MIN_CACHE_TTL = 600;
    private const MAX_CACHE_TTL = 3600;
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

        if ($model === null) {
            return null;
        }

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
     * Encontra configuração por ID e usuário
     */
    public function findByIdAndUserId(UuidInterface $id, UuidInterface $userId): ?WebhookConfig
    {
        $cacheKey = "webhook_config:id:{$id->toString()}:user:{$userId->toString()}";

        return $this->findWithCache($cacheKey, function () use ($id, $userId) {
            $model = WebhookConfigModel::where('id', $id->toString())
                ->where('user_id', $userId->toString())
                ->whereNull('deleted_at')
                ->first();

            return $model ? $this->toDomain($model) : null;
        }, self::MAX_CACHE_TTL);
    }

    /**
     * Encontra todas as configurações de webhook de um usuário
     */
    public function findAllByUserId(UuidInterface $userId): array
    {
        $cacheKey = "webhook_configs:user:{$userId->toString()}";

        return $this->findWithCache($cacheKey, function () use ($userId) {
            $models = WebhookConfigModel::where('user_id', $userId->toString())
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            $webhookConfigs = [];
            foreach ($models as $model) {
                $webhookConfigs[] = $this->toDomain($model);
            }

            return $webhookConfigs;
        }, self::MAX_CACHE_TTL);
    }

    /**
     * Remove configuração (soft delete)
     */
    public function delete(UuidInterface $id): void
    {
        $model = WebhookConfigModel::find($id->toString());

        if ($model !== null) {
            $this->deleteModel($model);
        }
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
