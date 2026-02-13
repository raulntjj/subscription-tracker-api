<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Modules\User\Domain\Entities\User;
use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class CreateUserUseCase
{
    use Loggable;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function execute(
        string $name,
        string $email,
        string $password,
        ?string $surname = null,
        ?string $profilePath = null
    ): UserDTO {
        $this->logger()->info('Creating new user', [
            'email' => $email,
            'name' => $name,
            'surname' => $surname,
        ]);

        try {
            $user = new User(
                id: Uuid::uuid4(),
                name: $name,
                email: new Email($email),
                password: Password::fromPlainText($password),
                createdAt: new DateTimeImmutable(),
                surname: $surname,
                profilePath: $profilePath
            );

            // Persiste no MySQL e invalida o cache da tag 'users'
            $this->userRepository->save($user);

            $this->logger()->event('UserCreated', [
                'user_id' => $user->id()->toString(),
                'email' => $user->email()->value(),
            ]);

            $this->logger()->audit(
                action: 'create',
                entityType: 'User',
                entityId: $user->id()->toString(),
                context: [
                    'email' => $user->email()->value(),
                    'name' => $user->name(),
                    'surname' => $user->surname(),
                ]
            );

            return UserDTO::fromEntity($user);
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to create user', [
                'email' => $email,
                'name' => $name,
            ], $e);

            throw $e;
        }
    }
}
