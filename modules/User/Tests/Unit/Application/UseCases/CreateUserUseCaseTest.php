<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\UseCases;

use DateTimeImmutable;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Application\UseCases\CreateUserUseCase;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Domain\Entities\User;

final class CreateUserUseCaseTest extends TestCase
{
    private MockObject&UserRepositoryInterface $userRepository;
    private CreateUserUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new CreateUserUseCase($this->userRepository);
    }

    public function test_creates_user_with_required_fields(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $result = $this->useCase->execute(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
        );

        $this->assertInstanceOf(UserDTO::class, $result);
        $this->assertEquals('John', $result->name);
        $this->assertEquals('john@example.com', $result->email);
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->createdAt);
        $this->assertNull($result->surname);
        $this->assertNull($result->profilePath);
    }

    public function test_creates_user_with_all_fields(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->useCase->execute(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
            surname: 'Doe',
            profilePath: '/uploads/avatar.jpg',
        );

        $this->assertEquals('John', $result->name);
        $this->assertEquals('Doe', $result->surname);
        $this->assertEquals('/uploads/avatar.jpg', $result->profilePath);
    }

    public function test_creates_user_with_valid_uuid(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        $result = $this->useCase->execute(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
        );

        // Verifica que o ID gerado é um UUID válido
        $this->assertTrue(Uuid::isValid($result->id));
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->useCase->execute(
            name: 'John',
            email: 'invalid-email',
            password: 'SecurePass123',
        );
    }

    public function test_throws_exception_for_short_password(): void
    {
        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $this->useCase->execute(
            name: 'John',
            email: 'john@example.com',
            password: 'short',
        );
    }

    public function test_passes_correct_entity_to_repository(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user): bool {
                return $user->name() === 'John'
                    && $user->email()->value() === 'john@example.com'
                    && $user->surname() === 'Doe'
                    && $user->profilePath() === '/uploads/avatar.jpg'
                    && $user->password()->verify('SecurePass123')
                    && $user->createdAt() instanceof DateTimeImmutable;
            }));

        $this->useCase->execute(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
            surname: 'Doe',
            profilePath: '/uploads/avatar.jpg',
        );
    }

    public function test_rethrows_repository_exception(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->useCase->execute(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
        );
    }
}
