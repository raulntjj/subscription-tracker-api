<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Throwable;
use Ramsey\Uuid\Uuid;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class DeleteUserUseCase
{
    use Loggable;

    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->logger()->info('Deleting user', [
            'user_id' => $id,
        ]);

        try {
            $uuid = Uuid::fromString($id);

            $user = $this->userRepository->findById($uuid);

            if ($user === null) {
                throw new \InvalidArgumentException("User not found with id: {$id}");
            }

            $this->userRepository->delete($uuid);

            $this->logger()->event('UserDeleted', [
                'user_id' => $id,
            ]);

            $this->logger()->audit(
                action: 'delete',
                entityType: 'User',
                entityId: $id,
                context: [
                    'email' => $user->email()->value(),
                    'name' => $user->name(),
                ],
            );
        } catch (Throwable $e) {
            $this->logger()->error('Failed to delete user', [
                'user_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
