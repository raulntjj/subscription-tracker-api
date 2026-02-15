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
