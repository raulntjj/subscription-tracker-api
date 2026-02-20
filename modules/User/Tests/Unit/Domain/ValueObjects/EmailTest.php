<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Domain\ValueObjects;

use ReflectionClass;
use InvalidArgumentException;
use Modules\User\Tests\UserTestCase;
use Modules\User\Domain\ValueObjects\Email;

final class EmailTest extends UserTestCase
{
    public function test_creates_valid_email(): void
    {
        $email = new Email('john@example.com');

        $this->assertEquals('john@example.com', $email->value());
    }

    public function test_converts_email_to_lowercase(): void
    {
        $email = new Email('JOHN@EXAMPLE.COM');

        $this->assertEquals('john@example.com', $email->value());
    }

    public function test_trims_whitespace_from_email(): void
    {
        $email = new Email('  john@example.com  ');

        $this->assertEquals('john@example.com', $email->value());
    }

    public function test_throws_exception_for_invalid_email_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('invalid-email');
    }

    public function test_throws_exception_for_email_without_at_symbol(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('invalidemail.com');
    }

    public function test_throws_exception_for_email_without_domain(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('john@');
    }

    public function test_throws_exception_for_empty_email(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('');
    }

    public function test_equals_returns_true_for_same_email(): void
    {
        $email1 = new Email('john@example.com');
        $email2 = new Email('john@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function test_equals_returns_false_for_different_emails(): void
    {
        $email1 = new Email('john@example.com');
        $email2 = new Email('jane@example.com');

        $this->assertFalse($email1->equals($email2));
    }

    public function test_equals_returns_true_for_case_insensitive_emails(): void
    {
        $email1 = new Email('JOHN@EXAMPLE.COM');
        $email2 = new Email('john@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function test_to_string_returns_email_value(): void
    {
        $email = new Email('john@example.com');

        $this->assertEquals('john@example.com', (string) $email);
    }

    public function test_accepts_valid_email_with_subdomain(): void
    {
        $email = new Email('john@mail.example.com');

        $this->assertEquals('john@mail.example.com', $email->value());
    }

    public function test_accepts_valid_email_with_plus_sign(): void
    {
        $email = new Email('john+test@example.com');

        $this->assertEquals('john+test@example.com', $email->value());
    }

    public function test_accepts_valid_email_with_numbers(): void
    {
        $email = new Email('john123@example.com');

        $this->assertEquals('john123@example.com', $email->value());
    }

    public function test_accepts_valid_email_with_dots(): void
    {
        $email = new Email('john.doe@example.com');

        $this->assertEquals('john.doe@example.com', $email->value());
    }

    public function test_throws_exception_for_email_with_spaces(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('john doe@example.com');
    }

    public function test_is_readonly(): void
    {
        $email = new Email('john@example.com');

        $reflection = new ReflectionClass($email);
        $this->assertTrue($reflection->isReadOnly());
    }
}
