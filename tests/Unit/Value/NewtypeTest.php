<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Value;

use Exact\Value\Newtype;
use PHPUnit\Framework\TestCase;

final class NewtypeTest extends TestCase
{
    public function test_it_stores_and_returns_value(): void
    {
        $newtype = new TestNewtype(42);

        self::assertSame(42, $newtype->value());
    }

    public function test_same_newtype_values_are_equal(): void
    {
        $first = new TestNewtype(42);
        $second = new TestNewtype(42);

        self::assertTrue($first->equals($second));
    }

    public function test_different_newtype_values_are_not_equal(): void
    {
        $first = new TestNewtype(42);
        $second = new TestNewtype(100);

        self::assertFalse($first->equals($second));
    }

    public function test_different_newtype_classes_are_not_equal(): void
    {
        $userId = new UserId(42);
        $orderId = new OrderId(42);

        self::assertFalse($userId->equals($orderId));
    }

    public function test_it_supports_different_value_types(): void
    {
        $string = new TestNewtype('exact');
        $array = new TestNewtype(['foo', 'bar']);

        self::assertSame('exact', $string->value());
        self::assertSame(['foo', 'bar'], $array->value());
    }

    public function test_it_is_immutable(): void
    {
        $reflection = new \ReflectionClass(TestNewtype::class);

        self::assertTrue($reflection->isReadOnly());
    }
}

final readonly class TestNewtype extends Newtype
{
}

final readonly class UserId extends Newtype
{
}

final readonly class OrderId extends Newtype
{
}