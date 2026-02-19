<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\User\Tests\UserTestCase;
use Modules\User\Domain\ValueObjects\Password;

final class PasswordTest extends UserTestCase
{
    public function test_creates_password_from_plain_text(): void
    {
        $password = Password::fromPlainText('SecurePass123');

        $this->assertInstanceOf(Password::class, $password);
        $this->assertNotEmpty($password->value());
    }

    public function test_hashes_plain_text_password(): void
    {
        $plainPassword = 'SecurePass123';
        $password = Password::fromPlainText($plainPassword);

        // O valor hash não deve ser igual ao texto plano
        $this->assertNotEquals($plainPassword, $password->value());

        // O hash deve começar com $2y$ (bcrypt)
        $this->assertStringStartsWith('$2y$', $password->value());
    }

    public function test_throws_exception_for_short_password(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        Password::fromPlainText('short');
    }

    public function test_throws_exception_for_7_character_password(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        Password::fromPlainText('1234567');
    }

    public function test_accepts_8_character_password(): void
    {
        $password = Password::fromPlainText('12345678');

        $this->assertInstanceOf(Password::class, $password);
    }

    public function test_accepts_long_password(): void
    {
        $longPassword = str_repeat('a', 100);
        $password = Password::fromPlainText($longPassword);

        $this->assertInstanceOf(Password::class, $password);
    }

    public function test_creates_password_from_hash(): void
    {
        $hash = password_hash('SecurePass123', PASSWORD_BCRYPT);
        $password = Password::fromHash($hash);

        $this->assertInstanceOf(Password::class, $password);
        $this->assertEquals($hash, $password->value());
    }

    public function test_verify_returns_true_for_correct_password(): void
    {
        $plainPassword = 'SecurePass123';
        $password = Password::fromPlainText($plainPassword);

        $this->assertTrue($password->verify($plainPassword));
    }

    public function test_verify_returns_false_for_incorrect_password(): void
    {
        $password = Password::fromPlainText('SecurePass123');

        $this->assertFalse($password->verify('WrongPassword'));
    }

    public function test_verify_returns_false_for_empty_password(): void
    {
        $password = Password::fromPlainText('SecurePass123');

        $this->assertFalse($password->verify(''));
    }

    public function test_verify_is_case_sensitive(): void
    {
        $password = Password::fromPlainText('SecurePass123');

        $this->assertFalse($password->verify('securepass123'));
        $this->assertFalse($password->verify('SECUREPASS123'));
    }

    public function test_to_string_returns_hash_value(): void
    {
        $password = Password::fromPlainText('SecurePass123');

        $this->assertEquals($password->value(), (string) $password);
        $this->assertStringStartsWith('$2y$', (string) $password);
    }

    public function test_different_passwords_generate_different_hashes(): void
    {
        $password1 = Password::fromPlainText('SecurePass123');
        $password2 = Password::fromPlainText('DifferentPass456');

        $this->assertNotEquals($password1->value(), $password2->value());
    }

    public function test_same_password_generates_different_hashes_each_time(): void
    {
        // bcrypt usa salt aleatório, então o mesmo password gera hashes diferentes
        $password1 = Password::fromPlainText('SecurePass123');
        $password2 = Password::fromPlainText('SecurePass123');

        $this->assertNotEquals($password1->value(), $password2->value());

        // Mas ambos devem verificar corretamente
        $this->assertTrue($password1->verify('SecurePass123'));
        $this->assertTrue($password2->verify('SecurePass123'));
    }

    public function test_accepts_password_with_special_characters(): void
    {
        $password = Password::fromPlainText('P@ssw0rd!#$%');

        $this->assertInstanceOf(Password::class, $password);
        $this->assertTrue($password->verify('P@ssw0rd!#$%'));
    }

    public function test_accepts_password_with_spaces(): void
    {
        $password = Password::fromPlainText('Pass word 123');

        $this->assertInstanceOf(Password::class, $password);
        $this->assertTrue($password->verify('Pass word 123'));
    }

    public function test_accepts_password_with_unicode_characters(): void
    {
        $password = Password::fromPlainText('Señor123');

        $this->assertInstanceOf(Password::class, $password);
        $this->assertTrue($password->verify('Señor123'));
    }

    public function test_is_readonly(): void
    {
        $password = Password::fromPlainText('SecurePass123');

        // Verifica que a classe é readonly
        $reflection = new \ReflectionClass($password);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_from_hash_does_not_validate_length(): void
    {
        // fromHash não valida o tamanho porque já é um hash
        $shortHash = 'short';
        $password = Password::fromHash($shortHash);

        $this->assertEquals($shortHash, $password->value());
    }
}
