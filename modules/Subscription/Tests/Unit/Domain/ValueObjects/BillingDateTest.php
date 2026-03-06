<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Domain\ValueObjects;

use ReflectionClass;
use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\ValueObjects\BillingDate;

final class BillingDateTest extends SubscriptionTestCase
{
    public function test_creates_from_datetime(): void
    {
        $date = new DateTimeImmutable('tomorrow');
        $billingDate = BillingDate::fromDateTime($date);

        $this->assertEquals($date->format('Y-m-d'), $billingDate->format());
    }

    public function test_creates_from_string(): void
    {
        $tomorrow = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
        $billingDate = BillingDate::fromString($tomorrow);

        $this->assertEquals($tomorrow, $billingDate->format());
    }

    public function test_creates_today(): void
    {
        $billingDate = BillingDate::today();

        $this->assertEquals((new DateTimeImmutable('today'))->format('Y-m-d'), $billingDate->format());
    }

    public function test_creates_future_date(): void
    {
        $futureDate = new DateTimeImmutable('+30 days');
        $billingDate = BillingDate::fromDateTime($futureDate);

        $this->assertEquals($futureDate->format('Y-m-d'), $billingDate->format());
    }


    public function test_throws_exception_for_past_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BillingDate::fromString('2020-01-01');
    }

    public function test_throws_exception_for_yesterday(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BillingDate::fromDateTime(new DateTimeImmutable('yesterday'));
    }


    public function test_value_returns_datetime_immutable(): void
    {
        $billingDate = BillingDate::today();

        $this->assertInstanceOf(DateTimeImmutable::class, $billingDate->value());
    }


    public function test_format_defaults_to_y_m_d(): void
    {
        $billingDate = BillingDate::today();

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $billingDate->format());
    }

    public function test_format_with_custom_format(): void
    {
        $billingDate = BillingDate::today();

        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4}$/', $billingDate->format('d/m/Y'));
    }


    public function test_add_months_returns_new_billing_date(): void
    {
        $billingDate = BillingDate::today();
        $next = $billingDate->addMonths(1);

        $this->assertNotEquals($billingDate->format(), $next->format());
    }

    public function test_add_months_does_not_mutate_original(): void
    {
        $billingDate = BillingDate::today();
        $original = $billingDate->format();

        $billingDate->addMonths(3);

        $this->assertEquals($original, $billingDate->format());
    }

    public function test_add_multiple_months(): void
    {
        $billingDate = BillingDate::today();
        $next = $billingDate->addMonths(6);

        $expected = (new DateTimeImmutable('today'))->modify('+6 months')->format('Y-m-d');
        $this->assertEquals($expected, $next->format());
    }


    public function test_is_today_returns_true_for_today(): void
    {
        $billingDate = BillingDate::today();

        $this->assertTrue($billingDate->isToday());
    }

    public function test_is_today_returns_false_for_future_date(): void
    {
        $billingDate = BillingDate::fromDateTime(new DateTimeImmutable('+5 days'));

        $this->assertFalse($billingDate->isToday());
    }

    public function test_is_future_returns_true_for_future_date(): void
    {
        $billingDate = BillingDate::fromDateTime(new DateTimeImmutable('+5 days'));

        $this->assertTrue($billingDate->isFuture());
    }

    public function test_is_future_returns_false_for_today(): void
    {
        $billingDate = BillingDate::today();

        $this->assertFalse($billingDate->isFuture());
    }

    public function test_is_past_returns_false_for_today(): void
    {
        $billingDate = BillingDate::today();

        $this->assertFalse($billingDate->isPast());
    }

    public function test_is_past_returns_false_for_future(): void
    {
        $billingDate = BillingDate::fromDateTime(new DateTimeImmutable('+1 day'));

        $this->assertFalse($billingDate->isPast());
    }


    public function test_equals_returns_true_for_same_date(): void
    {
        $a = BillingDate::today();
        $b = BillingDate::today();

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_dates(): void
    {
        $a = BillingDate::today();
        $b = BillingDate::fromDateTime(new DateTimeImmutable('+5 days'));

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_returns_false_for_null(): void
    {
        $billingDate = BillingDate::today();

        $this->assertFalse($billingDate->equals(null));
    }


    public function test_to_string_returns_formatted_date(): void
    {
        $billingDate = BillingDate::today();

        $this->assertEquals($billingDate->format(), (string) $billingDate);
    }

    public function test_json_serialize_returns_formatted_string(): void
    {
        $billingDate = BillingDate::today();

        $this->assertEquals($billingDate->format(), $billingDate->jsonSerialize());
    }

    public function test_json_encode_produces_quoted_string(): void
    {
        $billingDate = BillingDate::today();
        $expected = '"' . $billingDate->format() . '"';

        $this->assertEquals($expected, json_encode($billingDate));
    }


    public function test_is_readonly(): void
    {
        $billingDate = BillingDate::today();

        $reflection = new ReflectionClass($billingDate);
        $this->assertTrue($reflection->isReadOnly());
    }
}
