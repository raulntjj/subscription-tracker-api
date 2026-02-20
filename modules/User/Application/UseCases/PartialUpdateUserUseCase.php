<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Throwable;
use Ramsey\Uuid\Uuid;
use InvalidArgumentException;
use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;
use Modules\User\Application\DTOs\UpdateUserDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class PartialUpdateUserUseCase
{
    use Loggable;

    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Atualiza parcialmente os campos do usuário (PATCH)
     * Apenas os campos preenchidos no DTO serão atualizados
     */
    public function execute(string $id, UpdateUserDTO $dto): UserDTO
    {
        $this->logger()->info('Patching user', [
            'user_id' => $id,
            'fields' => array_keys($dto->toArray()),
        ]);

        try {
            $uuid = Uuid::fromString($id);

            $user = $this->userRepository->findById($uuid);

            if ($user === null) {
                throw new InvalidArgumentException(__('User::message.user_not_found_with_id', ['id' => $id]));
            }

            if (!$dto->hasChanges()) {
                throw new InvalidArgumentException(__('User::message.no_fields_for_update'));
            }

            if ($dto->name !== null) {
                $user->changeName($dto->name);
            }

            if ($dto->email !== null) {
                $user->changeEmail(new Email($dto->email));
            }

            if ($dto->password !== null) {
                $user->changePassword(Password::fromPlainText($dto->password));
            }

            $this->userRepository->update($user);

            $this->logger()->event('UserPatched', [
                'user_id' => $id,
                'updated_fields' => array_keys($dto->toArray()),
            ]);

            $this->logger()->audit(
                action: 'patch',
                entityType: 'User',
                entityId: $id,
                context: [
                    'updated_fields' => array_keys($dto->toArray()),
                    'email' => $user->email()->value(),
                    'name' => $user->name(),
                ],
            );

            return UserDTO::fromEntity($user);
        } catch (Throwable $e) {
            $this->logger()->error('Failed to patch user', [
                'user_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
