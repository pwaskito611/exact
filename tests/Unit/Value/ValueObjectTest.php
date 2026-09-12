<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Value;

use Exact\Value\ValueObject;
use PHPUnit\Framework\TestCase;

final class ValueObjectTest extends TestCase
{
    public function test_equal_value_objects_are_equal(): void
    {
        $first = new TestValueObject('foo');
        $second = new TestValueObject('foo');

        self::assertTrue($first->equals($second));
    }

    public function test_different_value_objects_are_not_equal(): void
    {
        $first = new TestValueObject('foo');
        $second = new TestValueObject('bar');

        self::assertFalse($first->equals($second));
    }

    public function test_value_object_is_immutable(): void
    {
        $reflection = new \ReflectionClass(TestValueObject::class);

        self::assertTrue($reflection->isReadOnly());
    }
}

final readonly class TestValueObject extends ValueObject
{
    public function __construct(
        private string $value,
    ) {}

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->value === $other->value;
    }
}