<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\DTOs;

use PHPUnit\Framework\TestCase;
use Modules\User\Application\DTOs\UpdateUserDTO;

final class UpdateUserDTOTest extends TestCase
{
    public function test_creates_dto_with_no_fields(): void
    {
        $dto = new UpdateUserDTO();

        $this->assertNull($dto->name);
        $this->assertNull($dto->email);
        $this->assertNull($dto->password);
        $this->assertNull($dto->surname);
        $this->assertNull($dto->profilePath);
    }

    public function test_creates_dto_with_all_fields(): void
    {
        $dto = new UpdateUserDTO(
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

    public function test_creates_from_array_with_partial_fields(): void
    {
        $dto = UpdateUserDTO::fromArray([
            'name' => 'Jane',
            'email' => 'jane@example.com',
        ]);

        $this->assertEquals('Jane', $dto->name);
        $this->assertEquals('jane@example.com', $dto->email);
        $this->assertNull($dto->password);
        $this->assertNull($dto->surname);
        $this->assertNull($dto->profilePath);
    }

    public function test_creates_from_array_with_all_fields(): void
    {
        $dto = UpdateUserDTO::fromArray([
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

    public function test_creates_from_empty_array(): void
    {
        $dto = UpdateUserDTO::fromArray([]);

        $this->assertNull($dto->name);
        $this->assertNull($dto->email);
        $this->assertNull($dto->password);
        $this->assertNull($dto->surname);
        $this->assertNull($dto->profilePath);
    }

    public function test_to_array_filters_null_values(): void
    {
        $dto = new UpdateUserDTO(
            name: 'John',
            email: 'john@example.com',
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'name' => 'John',
            'email' => 'john@example.com',
        ], $array);

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('surname', $array);
        $this->assertArrayNotHasKey('profile_path', $array);
    }

    public function test_to_array_with_all_fields(): void
    {
        $dto = new UpdateUserDTO(
            name: 'John',
            email: 'john@example.com',
            password: 'SecurePass123',
            surname: 'Doe',
            profilePath: '/uploads/john.png',
        );

        $array = $dto->toArray();

        $this->assertCount(5, $array);
        $this->assertEquals('John', $array['name']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertEquals('SecurePass123', $array['password']);
        $this->assertEquals('Doe', $array['surname']);
        $this->assertEquals('/uploads/john.png', $array['profile_path']);
    }

    public function test_has_changes_returns_true_when_any_field_is_set(): void
    {
        $this->assertTrue((new UpdateUserDTO(name: 'John'))->hasChanges());
        $this->assertTrue((new UpdateUserDTO(email: 'john@example.com'))->hasChanges());
        $this->assertTrue((new UpdateUserDTO(password: 'Pass12345'))->hasChanges());
        $this->assertTrue((new UpdateUserDTO(surname: 'Doe'))->hasChanges());
        $this->assertTrue((new UpdateUserDTO(profilePath: '/path'))->hasChanges());
    }

    public function test_has_changes_returns_false_when_no_fields_are_set(): void
    {
        $dto = new UpdateUserDTO();

        $this->assertFalse($dto->hasChanges());
    }

    public function test_has_changes_returns_false_from_empty_array(): void
    {
        $dto = UpdateUserDTO::fromArray([]);

        $this->assertFalse($dto->hasChanges());
    }
}
