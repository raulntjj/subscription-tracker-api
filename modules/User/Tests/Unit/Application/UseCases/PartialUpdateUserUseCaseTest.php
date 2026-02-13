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
use Modules\User\Application\UseCases\PartialUpdateUserUseCase;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;

final class PartialUpdateUserUseCaseTest extends TestCase
{
    private MockObject&UserRepositoryInterface $userRepository;
    private PartialUpdateUserUseCase $useCase;
    private string $userId;
    private User $existingUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new PartialUpdateUserUseCase($this->userRepository);

        $this->userId = '550e8400-e29b-41d4-a716-446655440000';
        $this->existingUser = new User(
            id: Uuid::fromString($this->userId),
            name: 'John',
            email: new Email('john@example.com'),
            password: Password::fromPlainText('OldPass12345'),
            createdAt: new DateTimeImmutable('2025-01-01 12:00:00'),
            surname: 'Doe',
            profilePath: '/uploads/old.png',
        );
    }

    public function test_patches_only_name(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (User $user): bool {
                return $user->name() === 'Jane'
                    && $user->email()->value() === 'john@example.com'
                    && $user->surname() === 'Doe';
            }));

        $dto = new UpdateUserDTO(name: 'Jane');
        $result = $this->useCase->execute($this->userId, $dto);

        $this->assertInstanceOf(UserDTO::class, $result);
        $this->assertEquals('Jane', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function test_patches_only_email(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (User $user): bool {
                return $user->email()->value() === 'newemail@example.com'
                    && $user->name() === 'John';
            }));

        $dto = new UpdateUserDTO(email: 'newemail@example.com');
        $result = $this->useCase->execute($this->userId, $dto);

        $this->assertEquals('newemail@example.com', $result->email);
    }

    public function test_patches_only_password(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update');

        $dto = new UpdateUserDTO(password: 'NewPass12345');
        $result = $this->useCase->execute($this->userId, $dto);

        $this->assertInstanceOf(UserDTO::class, $result);
    }

    public function test_patches_multiple_fields(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (User $user): bool {
                return $user->name() === 'Jane'
                    && $user->email()->value() === 'jane@example.com';
            }));

        $dto = new UpdateUserDTO(
            name: 'Jane',
            email: 'jane@example.com',
        );

        $result = $this->useCase->execute($this->userId, $dto);

        $this->assertEquals('Jane', $result->name);
        $this->assertEquals('jane@example.com', $result->email);
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

        $dto = new UpdateUserDTO(name: 'Jane');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("User not found with id: {$this->userId}");

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_throws_exception_when_no_changes_provided(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->never())
            ->method('update');

        $dto = new UpdateUserDTO();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No fields provided for update');

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $dto = new UpdateUserDTO(email: 'invalid-email');

        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_throws_exception_for_short_password(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $dto = new UpdateUserDTO(password: 'short');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $this->useCase->execute($this->userId, $dto);
    }

    public function test_does_not_alter_unchanged_fields(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (User $user): bool {
                return $user->name() === 'Jane'
                    && $user->email()->value() === 'john@example.com'
                    && $user->surname() === 'Doe'
                    && $user->profilePath() === '/uploads/old.png';
            }));

        $dto = new UpdateUserDTO(name: 'Jane');

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

        $dto = new UpdateUserDTO(name: 'Jane');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->useCase->execute($this->userId, $dto);
    }
}
