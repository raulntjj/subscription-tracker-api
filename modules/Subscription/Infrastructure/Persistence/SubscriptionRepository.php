<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Enums\CurrencyEnum;
use Modules\Subscription\Domain\Entities\Subscription;
use Modules\Subscription\Domain\Enums\BillingCycleEnum;
use Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Modules\Subscription\Domain\Enums\SubscriptionStatusEnum;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\SubscriptionModel;

final class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    private const MIN_CACHE_TTL = 600;
    private const MAX_CACHE_TTL = 3600;
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
            ],
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
        $cacheKey = "subscription:{$id->toString()}";

        return $this->findWithCache($cacheKey, function () use ($id) {
            $model = SubscriptionModel::find($id->toString());

            if ($model === null) {
                return null;
            }

            return $this->toEntity($model);
        }, self::MAX_CACHE_TTL);
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
        $models = SubscriptionModel::where('user_id', $userId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get();

        $subscriptions = [];

        /**
         * @var SubscriptionModel $model
         */
        foreach ($models as $model) {
            $subscriptions[] = $this->toEntity($model);
        }

        return $subscriptions;
    }

    public function findDueForBillingToday(): array
    {
        $today = now()->format('Y-m-d');

        $models = SubscriptionModel::where('status', 'active')
            ->whereDate('next_billing_date', $today)
            ->whereNull('deleted_at')
            ->get();

        $subscriptions = [];

        /**
         * @var SubscriptionModel $model
         */
        foreach ($models as $model) {
            $subscriptions[] = $this->toEntity($model);
        }

        return $subscriptions;
    }

    public function findPaginated(
        int $page,
        int $perPage,
        ?array $searchColumns = null,
        ?string $searchTerm = null,
        ?array $sorts = null,
    ): array {
        $searchKey = $searchTerm !== null && $searchTerm !== ''
            ? md5(json_encode($searchColumns) . $searchTerm)
            : 'none';
        $sortKey = $sorts !== null && count($sorts) > 0
            ? md5(json_encode($sorts))
            : 'default';
        $cacheKey = "subscriptions:paginated:page:{$page}:per_page:{$perPage}:search:{$searchKey}:sort:{$sortKey}";

        return $this->findWithCache($cacheKey, function () use ($page, $perPage, $searchColumns, $searchTerm, $sorts) {
            $query = SubscriptionModel::query()->whereNull('deleted_at');

            // Aplica busca
            if ($searchColumns !== null && $searchTerm !== null && $searchTerm !== '') {
                $query->where(function ($q) use ($searchColumns, $searchTerm) {
                    foreach ($searchColumns as $column) {
                        $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }

            // Aplica ordenação
            if ($sorts !== null && count($sorts) > 0) {
                foreach ($sorts as $sort) {
                    $query->orderBy($sort['column'], $sort['direction']);
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $total = $query->count();
            $lastPage = (int) ceil($total / $perPage);

            $models = $query
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $subscriptions = [];

            /**
             * @var SubscriptionModel $model
             */
            foreach ($models as $model) {
                $subscriptions[] = $this->toEntity($model);
            }

            return [
                'data' => $subscriptions,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
            ];
        }, self::MIN_CACHE_TTL);
    }

    public function findCursorPaginated(
        int $limit,
        ?string $cursor = null,
        ?array $searchColumns = null,
        ?string $searchTerm = null,
        ?array $sorts = null,
    ): array {
        $searchKey = $searchTerm !== null && $searchTerm !== ''
            ? md5(json_encode($searchColumns) . $searchTerm)
            : 'none';

        $sortKey = $sorts !== null && count($sorts) > 0
            ? md5(json_encode($sorts))
            : 'default';

        $cursorKey = $cursor ?? 'none';
        $cacheKey = "subscriptions:cursor_paginated:cursor:{$cursorKey}:limit:{$limit}:search:{$searchKey}:sort:{$sortKey}";

        return $this->findWithCache($cacheKey, function () use ($limit, $cursor, $searchColumns, $searchTerm, $sorts) {
            $query = SubscriptionModel::query()->whereNull('deleted_at');

            // Aplica busca
            if ($searchColumns !== null && $searchTerm !== null && $searchTerm !== '') {
                $query->where(function ($q) use ($searchColumns, $searchTerm) {
                    foreach ($searchColumns as $column) {
                        $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }

            // Aplica ordenação
            if ($sorts !== null && count($sorts) > 0) {
                foreach ($sorts as $sort) {
                    $query->orderBy($sort['column'], $sort['direction']);
                }
                $query->orderBy('id', 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
                $query->orderBy('id', 'desc');
            }

            /** @var \Illuminate\Pagination\CursorPaginator $paginator */
            $paginator = $query->cursorPaginate(
                perPage: $limit,
                cursor: $cursor ? \Illuminate\Pagination\Cursor::fromEncoded($cursor) : null,
            );

            // Converte para DTOs
            $subscriptions = $paginator->getCollection()
                ->map(fn ($model) => $this->toEntity($model))
                ->toArray();

            return [
                'subscriptions' => $subscriptions,
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ];
        }, self::MIN_CACHE_TTL);
    }

    public function findOptions(?array $searchColumns = null, ?string $searchTerm = null): array
    {
        $searchKey = $searchTerm !== null && $searchTerm !== ''
            ? md5(json_encode($searchColumns) . $searchTerm)
            : 'all';

        return $this->findWithCache("subscription_options:{$searchKey}", function () use ($searchColumns, $searchTerm) {
            $query = SubscriptionModel::query()
                ->whereNull('deleted_at')
                ->orderBy('name');

            // Aplica busca
            if ($searchColumns !== null && $searchTerm !== null && $searchTerm !== '') {
                $query->where(function ($q) use ($searchColumns, $searchTerm) {
                    foreach ($searchColumns as $column) {
                        $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }

            return $query
                ->get(['id', 'name'])
                ->map(fn ($model) => [
                    'value' => $model->id,
                    'label' => $model->name,
                ])
                ->toArray();
        });
    }

    private function toEntity(SubscriptionModel $model): Subscription
    {
        return new Subscription(
            id: Uuid::fromString($model->id),
            name: $model->name,
            price: (int) $model->price,
            currency: CurrencyEnum::from($model->currency),
            billingCycle: BillingCycleEnum::from($model->billing_cycle),
            nextBillingDate: new DateTimeImmutable($model->next_billing_date->format('Y-m-d')),
            category: $model->category,
            status: SubscriptionStatusEnum::from($model->status),
            userId: Uuid::fromString($model->user_id),
            createdAt: new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            updatedAt: $model->updated_at ? new DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null,
        );
    }
}
