<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\UseCases;

use DateTimeImmutable;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Modules\User\Application\UseCases\DeleteUserUseCase;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;

final class DeleteUserUseCaseTest extends TestCase
{
    private MockObject&UserRepositoryInterface $userRepository;
    private DeleteUserUseCase $useCase;
    private string $userId;
    private User $existingUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new DeleteUserUseCase($this->userRepository);

        $this->userId = '550e8400-e29b-41d4-a716-446655440000';
        $this->existingUser = new User(
            id: Uuid::fromString($this->userId),
            name: 'John',
            email: new Email('john@example.com'),
            password: Password::fromPlainText('SecurePass123'),
            createdAt: new DateTimeImmutable('2025-01-01 12:00:00'),
        );
    }

    public function test_deletes_existing_user(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $this->userId))
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('delete')
            ->with($this->callback(fn ($uuid) => $uuid->toString() === $this->userId));

        $this->useCase->execute($this->userId);
    }

    public function test_throws_exception_when_user_not_found(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("User not found with id: {$this->userId}");

        $this->useCase->execute($this->userId);
    }

    public function test_returns_void_on_success(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('delete');

        // Execute should not throw any exception
        $this->useCase->execute($this->userId);
        $this->assertTrue(true);
    }

    public function test_rethrows_repository_exception(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->useCase->execute($this->userId);
    }

    public function test_calls_find_before_delete(): void
    {
        $callOrder = [];

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'findById';
                return $this->existingUser;
            });

        $this->userRepository
            ->expects($this->once())
            ->method('delete')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'delete';
            });

        $this->useCase->execute($this->userId);

        $this->assertEquals(['findById', 'delete'], $callOrder);
    }
}
