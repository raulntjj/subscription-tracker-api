<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\DTOs;

use PHPUnit\Framework\TestCase;
use Modules\User\Application\DTOs\CreateUserDTO;

final class CreateUserDTOTest extends TestCase
{
    public function test_creates_dto_with_required_fields(): void
    {
        $dto = new CreateUserDTO(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
        );

        $this->assertEquals('John', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('SecurePass123', $dto->password);
        $this->assertNull($dto->surname);
        $this->assertNull($dto->profilePath);
    }

    public function test_creates_dto_with_all_fields(): void
    {
        $dto = new CreateUserDTO(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
            surname: 'Doe',
            profilePath: '/uploads/avatar.jpg',
        );

        $this->assertEquals('John', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('SecurePass123', $dto->password);
        $this->assertEquals('Doe', $dto->surname);
        $this->assertEquals('/uploads/avatar.jpg', $dto->profilePath);
    }

    public function test_creates_from_array_with_required_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'MyPass12345',
        ]);

        $this->assertEquals('Jane', $dto->name);
        $this->assertEquals('jane@example.com', $dto->email);
        $this->assertEquals('MyPass12345', $dto->password);
        $this->assertNull($dto->surname);
        $this->assertNull($dto->profilePath);
    }

    public function test_creates_from_array_with_all_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'MyPass12345',
            'surname' => 'Smith',
            'profile_path' => '/uploads/jane.png',
        ]);

        $this->assertEquals('Jane', $dto->name);
        $this->assertEquals('jane@example.com', $dto->email);
        $this->assertEquals('MyPass12345', $dto->password);
        $this->assertEquals('Smith', $dto->surname);
        $this->assertEquals('/uploads/jane.png', $dto->profilePath);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $dto = new CreateUserDTO(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
            surname: 'Doe',
            profilePath: '/uploads/john.png',
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'SecurePass123',
            'surname' => 'Doe',
            'profile_path' => '/uploads/john.png',
        ], $array);
    }

    public function test_to_array_includes_null_optional_fields(): void
    {
        $dto = new CreateUserDTO(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('surname', $array);
        $this->assertNull($array['surname']);
        $this->assertArrayHasKey('profile_path', $array);
        $this->assertNull($array['profile_path']);
    }
}
