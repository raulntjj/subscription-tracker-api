<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\User\Application\DTOs\UserDTO;
use Ramsey\Uuid\Uuid;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;
use Modules\User\Application\DTOs\UpdateUserDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class UpdateUserUseCase
{
    use Loggable;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Atualiza todos os campos do usuário (PUT)
     * Todos os campos são obrigatórios
     */
    public function execute(string $id, UpdateUserDTO $dto): UserDTO
    {
        $this->logger()->info('Updating user', [
            'user_id' => $id,
        ]);

        try {
            $uuid = Uuid::fromString($id);

            $user = $this->userRepository->findById($uuid);

            if ($user === null) {
                throw new \InvalidArgumentException("User not found with id: {$id}");
            }

            if ($dto->name === null || $dto->email === null || $dto->password === null) {
                throw new \InvalidArgumentException('All fields (name, email, password) are required for full update');
            }

            $user->changeName($dto->name);
            $user->changeEmail(new Email($dto->email));
            $user->changePassword(Password::fromPlainText($dto->password));

            if ($dto->surname !== null) {
                $user->changeSurname($dto->surname);
            }

            if ($dto->profilePath !== null) {
                $user->changeProfilePath($dto->profilePath);
            }

            $this->userRepository->update($user);

            $this->logger()->event('UserUpdated', [
                'user_id' => $id,
                'email' => $user->email()->value(),
            ]);

            $this->logger()->audit(
                action: 'update',
                entityType: 'User',
                entityId: $id,
                context: [
                    'email' => $user->email()->value(),
                    'name' => $user->name(),
                    'surname' => $user->surname(),
                ]
            );

            return UserDTO::fromEntity($user);
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to update user', [
                'user_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
