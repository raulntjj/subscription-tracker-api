<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\DTOs;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;

final class UserDTOTest extends TestCase
{
    private function makeUser(
        ?string $name = null,
        ?string $surname = null,
        ?string $profilePath = null,
    ): User {
        return new User(
            id: Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'),
            name: $name ?? 'John',
            email: new Email('john@example.com'),
            password: Password::fromPlainText('SecurePass123'),
            createdAt: new DateTimeImmutable('2025-01-15 10:30:00'),
            surname: $surname,
            profilePath: $profilePath,
        );
    }

    public function test_creates_from_entity_with_required_fields(): void
    {
        $user = $this->makeUser();
        $dto = UserDTO::fromEntity($user);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $dto->id);
        $this->assertEquals('John', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('2025-01-15 10:30:00', $dto->createdAt);
        $this->assertNull($dto->updatedAt);
        $this->assertNull($dto->surname);
        $this->assertNull($dto->profilePath);
    }

    public function test_creates_from_entity_with_all_fields(): void
    {
        $user = $this->makeUser(
            name: 'Jane',
            surname: 'Doe',
            profilePath: '/uploads/jane.png',
        );
        $dto = UserDTO::fromEntity($user);

        $this->assertEquals('Jane', $dto->name);
        $this->assertEquals('Doe', $dto->surname);
        $this->assertEquals('/uploads/jane.png', $dto->profilePath);
    }

    public function test_creates_from_database_object(): void
    {
        $data = (object) [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'John',
            'email' => 'john@example.com',
            'created_at' => '2025-01-15 10:30:00',
            'updated_at' => '2025-06-01 08:00:00',
            'surname' => 'Doe',
            'profile_path' => '/uploads/john.png',
        ];

        $dto = UserDTO::fromDatabase($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $dto->id);
        $this->assertEquals('John', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('2025-01-15 10:30:00', $dto->createdAt);
        $this->assertEquals('2025-06-01 08:00:00', $dto->updatedAt);
        $this->assertEquals('Doe', $dto->surname);
        $this->assertEquals('/uploads/john.png', $dto->profilePath);
    }

    public function test_creates_from_database_array(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'John',
            'email' => 'john@example.com',
            'created_at' => '2025-01-15 10:30:00',
        ];

        $dto = UserDTO::fromDatabase($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $dto->id);
        $this->assertEquals('John', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('2025-01-15 10:30:00', $dto->createdAt);
        $this->assertNull($dto->updatedAt);
        $this->assertNull($dto->surname);
        $this->assertNull($dto->profilePath);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $dto = new UserDTO(
            id: '550e8400-e29b-41d4-a716-446655440000',
            name: 'John',
            email: 'john@example.com',
            createdAt: '2025-01-15 10:30:00',
            updatedAt: '2025-06-01 08:00:00',
            surname: 'Doe',
            profilePath: '/uploads/john.png',
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john@example.com',
            'profile_path' => '/uploads/john.png',
            'created_at' => '2025-01-15 10:30:00',
            'updated_at' => '2025-06-01 08:00:00',
        ], $array);
    }

    public function test_to_array_includes_null_optional_fields(): void
    {
        $dto = new UserDTO(
            id: '550e8400-e29b-41d4-a716-446655440000',
            name: 'John',
            email: 'john@example.com',
            createdAt: '2025-01-15 10:30:00',
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('surname', $array);
        $this->assertNull($array['surname']);
        $this->assertArrayHasKey('profile_path', $array);
        $this->assertNull($array['profile_path']);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertNull($array['updated_at']);
    }

    public function test_to_options_with_surname(): void
    {
        $dto = new UserDTO(
            id: '550e8400-e29b-41d4-a716-446655440000',
            name: 'John',
            email: 'john@example.com',
            createdAt: '2025-01-15 10:30:00',
            surname: 'Doe',
        );

        $options = $dto->toOptions();

        $this->assertEquals([
            'value' => '550e8400-e29b-41d4-a716-446655440000',
            'label' => 'John Doe',
        ], $options);
    }

    public function test_to_options_without_surname(): void
    {
        $dto = new UserDTO(
            id: '550e8400-e29b-41d4-a716-446655440000',
            name: 'John',
            email: 'john@example.com',
            createdAt: '2025-01-15 10:30:00',
        );

        $options = $dto->toOptions();

        $this->assertEquals([
            'value' => '550e8400-e29b-41d4-a716-446655440000',
            'label' => 'John',
        ], $options);
    }

    public function test_from_entity_then_to_array_roundtrip(): void
    {
        $user = $this->makeUser(surname: 'Doe', profilePath: '/uploads/img.jpg');
        $dto = UserDTO::fromEntity($user);
        $array = $dto->toArray();

        $this->assertEquals($user->id()->toString(), $array['id']);
        $this->assertEquals($user->name(), $array['name']);
        $this->assertEquals($user->surname(), $array['surname']);
        $this->assertEquals($user->email()->value(), $array['email']);
        $this->assertEquals($user->profilePath(), $array['profile_path']);
        $this->assertEquals($user->createdAt()->format('Y-m-d H:i:s'), $array['created_at']);
    }
}
