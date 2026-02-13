<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Domain\Entities;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;

final class UserTest extends TestCase
{
    private function makeUser(
        ?string $name = null,
        ?string $email = null,
        ?string $password = null,
        ?string $surname = null,
        ?string $profilePath = null,
    ): User {
        return new User(
            id: Uuid::uuid4(),
            name: $name ?? 'John',
            email: new Email($email ?? 'john@example.com'),
            password: Password::fromPlainText($password ?? 'SecurePass123'),
            createdAt: new DateTimeImmutable('2025-01-01 12:00:00'),
            surname: $surname,
            profilePath: $profilePath,
        );
    }

    public function test_creates_user_with_required_fields(): void
    {
        $user = $this->makeUser();

        $this->assertNotEmpty($user->id()->toString());
        $this->assertEquals('John', $user->name());
        $this->assertEquals('john@example.com', $user->email()->value());
        $this->assertNotEmpty($user->password()->value());
        $this->assertInstanceOf(DateTimeImmutable::class, $user->createdAt());
        $this->assertNull($user->surname());
        $this->assertNull($user->profilePath());
    }

    public function test_creates_user_with_all_fields(): void
    {
        $user = $this->makeUser(
            name: 'John',
            surname: 'Doe',
            profilePath: '/uploads/john.png',
        );

        $this->assertEquals('John', $user->name());
        $this->assertEquals('Doe', $user->surname());
        $this->assertEquals('/uploads/john.png', $user->profilePath());
    }

    public function test_full_name_with_surname(): void
    {
        $user = $this->makeUser(name: 'John', surname: 'Doe');

        $this->assertEquals('John Doe', $user->fullName());
    }

    public function test_full_name_without_surname(): void
    {
        $user = $this->makeUser(name: 'John');

        $this->assertEquals('John', $user->fullName());
    }

    public function test_change_name(): void
    {
        $user = $this->makeUser(name: 'John');

        $user->changeName('Jane');

        $this->assertEquals('Jane', $user->name());
    }

    public function test_change_surname(): void
    {
        $user = $this->makeUser();

        $user->changeSurname('Smith');
        $this->assertEquals('Smith', $user->surname());

        $user->changeSurname(null);
        $this->assertNull($user->surname());
    }

    public function test_change_email(): void
    {
        $user = $this->makeUser(email: 'old@example.com');

        $newEmail = new Email('new@example.com');
        $user->changeEmail($newEmail);

        $this->assertEquals('new@example.com', $user->email()->value());
    }

    public function test_change_password(): void
    {
        $user = $this->makeUser(password: 'OldPass12345');

        $newPassword = Password::fromPlainText('NewPass12345');
        $user->changePassword($newPassword);

        $this->assertTrue($user->password()->verify('NewPass12345'));
        $this->assertFalse($user->password()->verify('OldPass12345'));
    }

    public function test_change_profile_path(): void
    {
        $user = $this->makeUser();

        $user->changeProfilePath('/uploads/avatar.jpg');
        $this->assertEquals('/uploads/avatar.jpg', $user->profilePath());

        $user->changeProfilePath(null);
        $this->assertNull($user->profilePath());
    }

    public function test_id_returns_uuid_interface(): void
    {
        $uuid = Uuid::uuid4();
        $user = new User(
            id: $uuid,
            name: 'Test',
            email: new Email('test@test.com'),
            password: Password::fromPlainText('TestPass123'),
            createdAt: new DateTimeImmutable(),
        );

        $this->assertSame($uuid, $user->id());
        $this->assertEquals($uuid->toString(), $user->id()->toString());
    }

    public function test_created_at_is_immutable(): void
    {
        $date = new DateTimeImmutable('2025-06-01 10:30:00');
        $user = new User(
            id: Uuid::uuid4(),
            name: 'Test',
            email: new Email('test@test.com'),
            password: Password::fromPlainText('TestPass123'),
            createdAt: $date,
        );

        $this->assertEquals('2025-06-01 10:30:00', $user->createdAt()->format('Y-m-d H:i:s'));
    }
}
