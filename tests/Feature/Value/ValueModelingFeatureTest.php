<?php

declare(strict_types=1);

namespace Exact\Tests\Feature\Value;

use Exact\Value\Newtype;
use Exact\Value\ValueObject;
use PHPUnit\Framework\TestCase;

final class ValueModelingFeatureTest extends TestCase
{
    public function test_it_models_an_identifier_as_a_typed_value(): void
    {
        $first = new UserId('user-1');
        $same = new UserId('user-1');
        $different = new UserId('user-2');

        self::assertSame('user-1', $first->value());
        self::assertTrue($first->equals($same));
        self::assertFalse($first->equals($different));
    }

    public function test_it_compares_value_objects_by_domain_values(): void
    {
        $first = new Money(100, 'USD');
        $same = new Money(100, 'USD');
        $different = new Money(100, 'EUR');

        self::assertTrue($first->equals($same));
        self::assertFalse($first->equals($different));
    }
}

final readonly class UserId extends Newtype
{
}

final readonly class Money extends ValueObject
{
    public function __construct(
        private readonly int $amount,
        private readonly string $currency,
    ) {
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}