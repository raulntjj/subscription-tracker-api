<?php

declare(strict_types=1);

namespace Modules\User\Domain\Contracts;

use Ramsey\Uuid\UuidInterface;
use Modules\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function update(User $user): void;

    public function findById(UuidInterface $id): ?User;

    public function findByEmail(string $email): ?User;

    public function delete(UuidInterface $id): void;
}
