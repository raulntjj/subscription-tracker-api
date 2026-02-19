<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Entities\BillingHistory;
use Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Modules\Subscription\Domain\Contracts\BillingHistoryRepositoryInterface;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\BillingHistoryModel;

final class BillingHistoryRepository extends BaseRepository implements BillingHistoryRepositoryInterface
{
    protected function getCacheTags(): array
    {
        return ['billing_histories'];
    }

    public function save(BillingHistory $entity): void
    {
        $this->upsert(
            BillingHistoryModel::class,
            ['id' => $entity->id()->toString()],
            [
                'subscription_id' => $entity->subscriptionId()->toString(),
                'amount_paid' => $entity->amountPaid(),
                'paid_at' => $entity->paidAt(),
                'created_at' => $entity->createdAt(),
            ]
        );
    }

    public function findById(UuidInterface $id): ?BillingHistory
    {
        $model = BillingHistoryModel::find($id->toString());

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findBySubscriptionId(string $subscriptionId): array
    {
        return BillingHistoryModel::where('subscription_id', $subscriptionId)
            ->whereNull('deleted_at')
            ->orderBy('paid_at', 'desc')
            ->get()
            ->map(fn ($model) => (object) [
                'id' => $model->id,
                'subscription_id' => $model->subscription_id,
                'amount_paid' => (int) $model->amount_paid,
                'paid_at' => $model->paid_at->format('Y-m-d H:i:s'),
                'created_at' => $model->created_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }

    public function getTotalPaidInPeriod(string $userId, string $startDate, string $endDate): int
    {
        return BillingHistoryModel::join('subscriptions', 'billing_histories.subscription_id', '=', 'subscriptions.id')
            ->where('subscriptions.user_id', $userId)
            ->whereBetween('billing_histories.paid_at', [$startDate, $endDate])
            ->whereNull('billing_histories.deleted_at')
            ->whereNull('subscriptions.deleted_at')
            ->sum('billing_histories.amount_paid');
    }

    private function toDomain(BillingHistoryModel $model): BillingHistory
    {
        return new BillingHistory(
            id: Uuid::fromString($model->id),
            subscriptionId: Uuid::fromString($model->subscription_id),
            amountPaid: (int) $model->amount_paid,
            paidAt: new DateTimeImmutable($model->paid_at->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s'))
        );
    }
}
