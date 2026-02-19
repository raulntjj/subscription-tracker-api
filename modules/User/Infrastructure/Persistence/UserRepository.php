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
            ]
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
        $model = UserModel::find($id->toString());

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByEmail(string $email): ?User
    {
        $model = UserModel::where('email', $email)->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
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
        $models = UserModel::orderBy('created_at', 'desc')->get();

        return $models->map(fn (UserModel $model) => $this->toDomain($model))->all();
    }

    public function findPaginated(int $page, int $perPage): array
    {
        $paginator = UserModel::orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->items() ? array_map(
                fn (UserModel $model) => $this->toDomain($model),
                $paginator->items()
            ) : [],
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function findCursorPaginated(int $limit, ?string $cursor = null): array
    {
        $query = UserModel::orderBy('created_at', 'desc');

        if ($cursor !== null) {
            $query->where('created_at', '<', $cursor);
        }

        $models = $query->limit($limit + 1)->get();

        $hasMore = $models->count() > $limit;
        if ($hasMore) {
            $models = $models->slice(0, $limit);
        }

        $data = $models->map(fn (UserModel $model) => $this->toDomain($model))->all();

        $nextCursor = null;
        if ($hasMore && count($data) > 0) {
            $lastItem = end($data);
            $nextCursor = $lastItem->createdAt()->format('Y-m-d H:i:s');
        }

        // Para cursor anterior, seria necessário inverter a query
        $prevCursor = $cursor; // Simplificado

        return [
            'data' => $data,
            'next_cursor' => $nextCursor,
            'prev_cursor' => $prevCursor,
        ];
    }

    public function findOptions(): array
    {
        return UserModel::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (UserModel $model) => [
                'id' => $model->id,
                'name' => $model->name . ($model->surname ? " {$model->surname}" : ''),
            ])
            ->all();
    }

    private function toDomain(UserModel $model): User
    {
        return new User(
            id: Uuid::fromString($model->id),
            name: $model->name,
            email: new Email($model->email),
            password: Password::fromHash($model->password),
            createdAt: new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            surname: $model->surname,
            profilePath: $model->profile_path
        );
    }
}
