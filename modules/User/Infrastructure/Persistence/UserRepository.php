<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Modules\User\Infrastructure\Persistence\Eloquent\UserModel;

final class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    private const MIN_CACHE_TTL = 600;
    private const MAX_CACHE_TTL = 3600;

    protected function getCacheTags(): array
    {
        return ['users'];
    }

    public function save(User $user): void
    {
        $this->upsert(
            UserModel::class,
            ['id' => $user->id()->toString()],
            [
                'name' => $user->name(),
                'email' => $user->email()->value(),
                'password' => $user->password()->value(),
                'surname' => $user->surname(),
                'profile_path' => $user->profilePath(),
                'created_at' => $user->createdAt(),
            ],
        );
    }

    public function update(User $user): void
    {
        $model = UserModel::find($user->id()->toString());

        if ($model === null) {
            return;
        }

        $model->name = $user->name();
        $model->email = $user->email()->value();
        $model->password = $user->password()->value();
        $model->surname = $user->surname();
        $model->profile_path = $user->profilePath();

        $this->saveModel($model);
    }

    public function findById(UuidInterface $id): ?User
    {
        $cacheKey = "user:{$id->toString()}";

        return $this->findWithCache($cacheKey, function () use ($id) {
            $model = UserModel::find($id->toString());

            if ($model === null) {
                return null;
            }

            return $this->toEntity($model);
        }, self::MAX_CACHE_TTL);
    }

    public function findByEmail(string $email): ?User
    {
        $cacheKey = "user:email:{$email}";

        return $this->findWithCache($cacheKey, function () use ($email) {
            $model = UserModel::where('email', $email)->first();

            if ($model === null) {
                return null;
            }

            return $this->toEntity($model);
        }, self::MAX_CACHE_TTL);
    }

    public function delete(UuidInterface $id): void
    {
        $model = UserModel::find($id->toString());

        if ($model !== null) {
            $this->deleteModel($model);
        }
    }

    public function findAll(): array
    {
        $cacheKey = "users:all";

        return $this->findWithCache($cacheKey, function () {
            $models = UserModel::orderBy('created_at', 'desc')->get();

            return $models->map(fn (UserModel $model) => $this->toEntity($model))->all();
        }, self::MAX_CACHE_TTL);
    }

    public function findPaginated(
        int $page,
        int $perPage,
        ?array $searchColumns = null,
        ?string $searchTerm = null,
        ?array $sorts = null,
    ): array {
        $cacheKey = "users:paginated:page:{$page}:per_page:{$perPage}";

        return $this->findWithCache($cacheKey, function () use ($page, $perPage, $searchColumns, $searchTerm, $sorts) {
            $query = UserModel::query();

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

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'users' => $paginator->items() ? array_map(
                    fn (UserModel $model) => $this->toEntity($model),
                    $paginator->items(),
                ) : [],
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
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
        $cacheKey = "users:cursor_paginated:cursor:{$cursorKey}:limit:{$limit}:search:{$searchKey}:sort:{$sortKey}";

        return $this->findWithCache($cacheKey, function () use ($limit, $cursor, $searchColumns, $searchTerm, $sorts) {
            $query = UserModel::query();

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

            // Converte para entidades de domínio
            $data = $paginator->getCollection()
                ->map(fn (UserModel $model) => $this->toEntity($model))
                ->toArray();

            return [
                'users' => $data,
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ];
        }, self::MIN_CACHE_TTL);
    }

    public function findOptions(): array
    {
        $cacheKey = "users:options";

        return $this->findWithCache($cacheKey, function () {
            return UserModel::select('id', 'name', 'surname')
                ->orderBy('name')
                ->get()
                ->map(fn (UserModel $model) => [
                    'id' => $model->id,
                    'name' => $model->name . ($model->surname ? " {$model->surname}" : ''),
                ])
                ->all();
        }, self::MIN_CACHE_TTL);
    }

    private function toEntity(UserModel $model): User
    {
        return new User(
            id: Uuid::fromString($model->id),
            name: $model->name,
            email: new Email($model->email),
            password: Password::fromHash($model->password),
            createdAt: new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            surname: $model->surname,
            profilePath: $model->profile_path,
        );
    }
}
