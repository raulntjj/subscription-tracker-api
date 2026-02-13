<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\UseCases;

use DateTimeImmutable;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Modules\User\Application\DTOs\UpdateUserDTO;
use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Application\UseCases\UpdateUserUseCase;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;

final class UpdateUserUseCaseTest extends TestCase
{
    private MockObject&UserRepositoryInterface $userRepository;
    private UpdateUserUseCase $useCase;
    private string $userId;
    private User $existingUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new UpdateUserUseCase($this->userRepository);

        $this->userId = '550e8400-e29b-41d4-a716-446655440000';
        $this->existingUser = new User(
            id: Uuid::fromString($this->userId),
            name: 'Old Name',
            email: new Email('old@example.com'),
            password: Password::fromPlainText('OldPass12345'),
            createdAt: new DateTimeImmutable('2025-01-01 12:00:00'),
            surname: 'OldSurname',
            profilePath: '/uploads/old.png',
        );
    }

    public function test_updates_user_with_all_required_fields(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $this->userId))
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->isInstanceOf(User::class));

        $dto = new UpdateUserDTO(
            name: 'New Name',
            email: 'new@example.com',
            password: 'NewPass12345',
            surname: 'NewSurname',
            profilePath: '/uploads/new.png',
        );

        $result = $this->useCase->execute($this->userId, $dto);

        $this->assertInstanceOf(UserDTO::class, $result);
        $this->assertEquals('New Name', $result->name);
        $this->assertEquals('new@example.com', $result->email);
        $this->assertEquals('NewSurname', $result->surname);
        $this->assertEquals('/uploads/new.png', $result->profilePath);
    }

    public function test_throws_exception_when_user_not_found(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->never())
            ->method('update');

        $dto = new UpdateUserDTO(
            name: 'New Name',
            email: 'new@example.com',
            password: 'NewPass12345',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("User not found with id: {$this->userId}");

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_throws_exception_when_name_is_null(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->never())
            ->method('update');

        $dto = new UpdateUserDTO(
            email: 'new@example.com',
            password: 'NewPass12345',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All fields (name, email, password) are required for full update');

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_throws_exception_when_email_is_null(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $dto = new UpdateUserDTO(
            name: 'New Name',
            password: 'NewPass12345',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All fields (name, email, password) are required for full update');

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_throws_exception_when_password_is_null(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $dto = new UpdateUserDTO(
            name: 'New Name',
            email: 'new@example.com',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All fields (name, email, password) are required for full update');

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_throws_exception_for_invalid_email_format(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $dto = new UpdateUserDTO(
            name: 'New Name',
            email: 'invalid-email',
            password: 'NewPass12345',
        );

        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_throws_exception_for_short_password(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $dto = new UpdateUserDTO(
            name: 'New Name',
            email: 'new@example.com',
            password: 'short',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_updates_optional_fields_when_provided(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (User $user): bool {
                return $user->name() === 'New Name'
                    && $user->email()->value() === 'new@example.com'
                    && $user->surname() === 'NewSurname'
                    && $user->profilePath() === '/uploads/new.png';
            }));

        $dto = new UpdateUserDTO(
            name: 'New Name',
            email: 'new@example.com',
            password: 'NewPass12345',
            surname: 'NewSurname',
            profilePath: '/uploads/new.png',
        );

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_keeps_optional_fields_when_not_provided(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (User $user): bool {
                // Os campos opcionais não alterados devem manter os valores originais
                return $user->surname() === 'OldSurname'
                    && $user->profilePath() === '/uploads/old.png';
            }));

        $dto = new UpdateUserDTO(
            name: 'New Name',
            email: 'new@example.com',
            password: 'NewPass12345',
        );

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_rethrows_repository_exception(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new \RuntimeException('Database error'));

        $dto = new UpdateUserDTO(
            name: 'New Name',
            email: 'new@example.com',
            password: 'NewPass12345',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->useCase->execute($this->userId, $dto);
    }
}
