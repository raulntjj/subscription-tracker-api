<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Domain\Entities;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;

final class UserTest extends TestCase
{
    private UuidInterface $uuid;
    private Email $email;
    private Password $password;
    private DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuid = Uuid::uuid4();
        $this->email = new Email('john@example.com');
        $this->password = Password::fromPlainText('SecurePass123');
        $this->createdAt = new DateTimeImmutable('2025-01-01 12:00:00');
    }

    public function test_creates_user_with_required_fields(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $this->assertEquals($this->uuid, $user->id());
        $this->assertEquals('John', $user->name());
        $this->assertNull($user->surname());
        $this->assertEquals($this->email, $user->email());
        $this->assertEquals($this->password, $user->password());
        $this->assertNull($user->profilePath());
        $this->assertEquals($this->createdAt, $user->createdAt());
    }

    public function test_creates_user_with_all_fields(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt,
            surname: 'Doe',
            profilePath: '/uploads/avatar.jpg'
        );

        $this->assertEquals('John', $user->name());
        $this->assertEquals('Doe', $user->surname());
        $this->assertEquals('/uploads/avatar.jpg', $user->profilePath());
    }

    public function test_id_returns_uuid_interface(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $this->assertInstanceOf(UuidInterface::class, $user->id());
    }

    public function test_email_returns_email_value_object(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $this->assertInstanceOf(Email::class, $user->email());
    }

    public function test_password_returns_password_value_object(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $this->assertInstanceOf(Password::class, $user->password());
    }

    public function test_created_at_returns_datetime_immutable(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $this->assertInstanceOf(DateTimeImmutable::class, $user->createdAt());
    }

    public function test_full_name_returns_name_only_when_no_surname(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $this->assertEquals('John', $user->fullName());
    }

    public function test_full_name_returns_name_and_surname(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt,
            surname: 'Doe'
        );

        $this->assertEquals('John Doe', $user->fullName());
    }

    public function test_full_name_trims_whitespace(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt,
            surname: null
        );

        // Quando surname é null, fullName não deve ter espaços extras
        $this->assertEquals('John', $user->fullName());
        $this->assertStringNotContainsString('  ', $user->fullName());
    }

    public function test_change_name_updates_user_name(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $user->changeName('Jane');

        $this->assertEquals('Jane', $user->name());
    }

    public function test_change_surname_updates_user_surname(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt,
            surname: 'Doe'
        );

        $user->changeSurname('Smith');

        $this->assertEquals('Smith', $user->surname());
    }

    public function test_change_surname_accepts_null(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt,
            surname: 'Doe'
        );

        $user->changeSurname(null);

        $this->assertNull($user->surname());
    }

    public function test_change_email_updates_user_email(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $newEmail = new Email('newemail@example.com');
        $user->changeEmail($newEmail);

        $this->assertEquals($newEmail, $user->email());
        $this->assertEquals('newemail@example.com', $user->email()->value());
    }

    public function test_change_password_updates_user_password(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $newPassword = Password::fromPlainText('NewSecurePass456');
        $user->changePassword($newPassword);

        $this->assertEquals($newPassword, $user->password());
        $this->assertTrue($user->password()->verify('NewSecurePass456'));
    }

    public function test_change_profile_path_updates_user_profile_path(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt,
            profilePath: '/uploads/old-avatar.jpg'
        );

        $user->changeProfilePath('/uploads/new-avatar.jpg');

        $this->assertEquals('/uploads/new-avatar.jpg', $user->profilePath());
    }

    public function test_change_profile_path_accepts_null(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt,
            profilePath: '/uploads/avatar.jpg'
        );

        $user->changeProfilePath(null);

        $this->assertNull($user->profilePath());
    }

    public function test_user_is_final_class(): void
    {
        $reflection = new \ReflectionClass(User::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function test_multiple_changes_persist(): void
    {
        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $user->changeName('Jane');
        $user->changeSurname('Smith');
        $newEmail = new Email('jane.smith@example.com');
        $user->changeEmail($newEmail);

        $this->assertEquals('Jane', $user->name());
        $this->assertEquals('Smith', $user->surname());
        $this->assertEquals('jane.smith@example.com', $user->email()->value());
        $this->assertEquals('Jane Smith', $user->fullName());
    }

    public function test_created_at_is_immutable(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01 12:00:00');

        $user = new User(
            id: $this->uuid,
            name: 'John',
            email: $this->email,
            password: $this->password,
            createdAt: $createdAt
        );

        $retrievedCreatedAt = $user->createdAt();

        // Modificar o objeto retornado não deve afetar o original
        $modifiedDate = $retrievedCreatedAt->modify('+1 day');

        $this->assertEquals($createdAt, $user->createdAt());
        $this->assertNotEquals($modifiedDate, $user->createdAt());
    }

    public function test_accepts_empty_name(): void
    {
        $user = new User(
            id: $this->uuid,
            name: '',
            email: $this->email,
            password: $this->password,
            createdAt: $this->createdAt
        );

        $this->assertEquals('', $user->name());
    }
}
