<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Entities\Subscription;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\Domain\Enums\BillingCycleEnum;
use Modules\Subscription\Domain\Enums\CurrencyEnum;
use Modules\Subscription\Domain\Enums\SubscriptionStatusEnum;
use Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\SubscriptionModel;

final class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    protected function getCacheTags(): array
    {
        return ['subscriptions'];
    }

    public function save(Subscription $entity): void
    {
        $this->upsert(
            SubscriptionModel::class,
            ['id' => $entity->id()->toString()],
            [
                'name' => $entity->name(),
                'price' => $entity->price(),
                'currency' => $entity->currency()->value,
                'billing_cycle' => $entity->billingCycle()->value,
                'next_billing_date' => $entity->nextBillingDate()->format('Y-m-d'),
                'category' => $entity->category(),
                'status' => $entity->status()->value,
                'user_id' => $entity->userId()->toString(),
                'created_at' => $entity->createdAt(),
                'updated_at' => $entity->updatedAt(),
            ]
        );
    }

    public function update(Subscription $entity): void
    {
        $model = SubscriptionModel::find($entity->id()->toString());

        if ($model === null) {
            return;
        }

        $model->name = $entity->name();
        $model->price = $entity->price();
        $model->currency = $entity->currency()->value;
        $model->billing_cycle = $entity->billingCycle()->value;
        $model->next_billing_date = $entity->nextBillingDate()->format('Y-m-d');
        $model->category = $entity->category();
        $model->status = $entity->status()->value;
        $model->updated_at = $entity->updatedAt();

        $this->saveModel($model);
    }

    public function findById(UuidInterface $id): ?Subscription
    {
        $model = SubscriptionModel::find($id->toString());

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function delete(UuidInterface $id): void
    {
        $model = SubscriptionModel::find($id->toString());

        if ($model !== null) {
            $this->deleteModel($model);
        }
    }

    public function findActiveByUserId(string $userId): array
    {
        return SubscriptionModel::where('user_id', $userId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get()
            ->map(fn($model) => (object) [
                'id' => $model->id,
                'name' => $model->name,
                'price' => (int) $model->price,
                'currency' => $model->currency,
                'billing_cycle' => $model->billing_cycle,
                'next_billing_date' => $model->next_billing_date,
                'category' => $model->category,
                'status' => $model->status,
                'user_id' => $model->user_id,
            ])
            ->toArray();
    }

    public function findDueForBillingToday(): array
    {
        $today = now()->format('Y-m-d');

        return SubscriptionModel::where('status', 'active')
            ->whereDate('next_billing_date', $today)
            ->whereNull('deleted_at')
            ->get()
            ->map(fn($model) => (object) [
                'id' => $model->id,
                'name' => $model->name,
                'price' => (int) $model->price,
                'currency' => $model->currency,
                'billing_cycle' => $model->billing_cycle,
                'next_billing_date' => $model->next_billing_date,
                'category' => $model->category,
                'status' => $model->status,
                'user_id' => $model->user_id,
            ])
            ->toArray();
    }

    private function toDomain(SubscriptionModel $model): Subscription
    {
        return new Subscription(
            id: Uuid::fromString($model->id),
            name: $model->name,
            price: (int) $model->price,
            currency: CurrencyEnum::from($model->currency),
            billingCycle: BillingCycleEnum::from($model->billing_cycle),
            nextBillingDate: new DateTimeImmutable($model->next_billing_date),
            category: $model->category,
            status: SubscriptionStatusEnum::from($model->status),
            userId: Uuid::fromString($model->user_id),
            createdAt: new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            updatedAt: $model->updated_at ? new DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null
        );
    }
}
