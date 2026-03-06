<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Domain\ValueObjects;

use ReflectionClass;
use InvalidArgumentException;
use Modules\Subscription\Domain\ValueObjects\Money;
use Modules\Subscription\Tests\SubscriptionTestCase;

final class MoneyTest extends SubscriptionTestCase
{
    public function test_creates_from_cents(): void
    {
        $money = Money::fromCents(1500);

        $this->assertEquals(1500, $money->toCents());
    }

    public function test_creates_from_units(): void
    {
        $money = Money::fromUnits(15.99);

        $this->assertEquals(1599, $money->toCents());
    }

    public function test_creates_zero_amount(): void
    {
        $money = Money::fromCents(0);

        $this->assertEquals(0, $money->toCents());
        $this->assertTrue($money->isZero());
    }

    public function test_from_units_rounds_correctly(): void
    {
        $money = Money::fromUnits(10.005);

        $this->assertEquals(1001, $money->toCents());
    }


    public function test_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(-1);
    }


    public function test_to_cents_returns_integer(): void
    {
        $money = Money::fromCents(1234);

        $this->assertIsInt($money->toCents());
        $this->assertEquals(1234, $money->toCents());
    }

    public function test_to_units_returns_float(): void
    {
        $money = Money::fromCents(1599);

        $this->assertIsFloat($money->toUnits());
        $this->assertEquals(15.99, $money->toUnits());
    }


    public function test_add_returns_correct_sum(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(500);

        $result = $a->add($b);

        $this->assertEquals(1500, $result->toCents());
    }

    public function test_subtract_returns_correct_difference(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(400);

        $result = $a->subtract($b);

        $this->assertEquals(600, $result->toCents());
    }

    public function test_subtract_throws_exception_when_result_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $a = Money::fromCents(100);
        $b = Money::fromCents(500);

        $a->subtract($b);
    }

    public function test_multiply_returns_correct_product(): void
    {
        $money = Money::fromCents(500);

        $result = $money->multiply(3);

        $this->assertEquals(1500, $result->toCents());
    }

    public function test_divide_returns_correct_quotient(): void
    {
        $money = Money::fromCents(1000);

        $result = $money->divide(4);

        $this->assertEquals(250, $result->toCents());
    }

    public function test_divide_rounds_correctly(): void
    {
        $money = Money::fromCents(1000);

        $result = $money->divide(3);

        $this->assertEquals(333, $result->toCents());
    }

    public function test_divide_by_zero_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $money = Money::fromCents(1000);
        $money->divide(0);
    }


    public function test_is_greater_than(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(500);

        $this->assertTrue($a->isGreaterThan($b));
        $this->assertFalse($b->isGreaterThan($a));
    }

    public function test_is_less_than(): void
    {
        $a = Money::fromCents(500);
        $b = Money::fromCents(1000);

        $this->assertTrue($a->isLessThan($b));
        $this->assertFalse($b->isLessThan($a));
    }

    public function test_is_zero(): void
    {
        $this->assertTrue(Money::fromCents(0)->isZero());
        $this->assertFalse(Money::fromCents(1)->isZero());
    }

    public function test_is_positive(): void
    {
        $this->assertTrue(Money::fromCents(1)->isPositive());
        $this->assertFalse(Money::fromCents(0)->isPositive());
    }


    public function test_equals_returns_true_for_same_amount(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(1000);

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_amounts(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(2000);

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_returns_false_for_null(): void
    {
        $money = Money::fromCents(1000);

        $this->assertFalse($money->equals(null));
    }


    public function test_to_string_returns_units(): void
    {
        $money = Money::fromCents(1599);

        $this->assertEquals('15.99', (string) $money);
    }

    public function test_json_serialize_returns_cents(): void
    {
        $money = Money::fromCents(1599);

        $this->assertEquals(1599, $money->jsonSerialize());
    }

    public function test_json_encode_produces_integer(): void
    {
        $money = Money::fromCents(1599);

        $this->assertEquals('1599', json_encode($money));
    }


    public function test_add_does_not_mutate_original(): void
    {
        $original = Money::fromCents(1000);
        $original->add(Money::fromCents(500));

        $this->assertEquals(1000, $original->toCents());
    }

    public function test_subtract_does_not_mutate_original(): void
    {
        $original = Money::fromCents(1000);
        $original->subtract(Money::fromCents(200));

        $this->assertEquals(1000, $original->toCents());
    }

    public function test_is_readonly(): void
    {
        $money = Money::fromCents(1000);

        $reflection = new ReflectionClass($money);
        $this->assertTrue($reflection->isReadOnly());
    }
}
