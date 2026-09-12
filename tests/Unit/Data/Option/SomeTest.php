<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Option;

use Exact\Data\Option\None;
use Exact\Data\Option\Option;
use Exact\Data\Option\Some;
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test_it_stores_value(): void
    {
        $some = new Some('value');

        self::assertSame('value', $some->value());
    }

    public function test_is_some(): void
    {
        $some = new Some('value');

        self::assertTrue($some->isSome());
        self::assertFalse($some->isNone());
    }

    public function test_map_transforms_value(): void
    {
        $some = new Some(10);

        $mapped = $some->map(
            fn (int $value): int => $value * 2
        );

        self::assertInstanceOf(Some::class, $mapped);
        self::assertSame(20, $mapped->value());
    }

    public function test_map_returns_new_option(): void
    {
        $some = new Some(10);

        $mapped = $some->map(
            fn (int $value): int => $value * 2
        );

        self::assertNotSame($some, $mapped);
    }

    public function test_flat_map_returns_option(): void
    {
        $some = new Some(10);

        $result = $some->flatMap(
            fn (int $value): Option => Option::some($value * 2)
        );

        self::assertInstanceOf(Some::class, $result);
        self::assertSame(20, $result->value());
    }

    public function test_flat_map_can_return_none(): void
    {
        $some = new Some(10);

        $result = $some->flatMap(
            fn (int $value): Option => Option::none()
        );

        self::assertInstanceOf(None::class, $result);
    }

    public function test_filter_keeps_value_when_predicate_matches(): void
    {
        $some = new Some(10);

        $result = $some->filter(
            fn (int $value): bool => $value > 5
        );

        self::assertSame($some, $result);
    }

    public function test_filter_returns_none_when_predicate_fails(): void
    {
        $some = new Some(10);

        $result = $some->filter(
            fn (int $value): bool => $value > 20
        );

        self::assertInstanceOf(None::class, $result);
    }

    public function test_fold_calls_on_some(): void
    {
        $some = new Some('Pandu');

        $result = $some->fold(
            fn (): string => 'None',
            fn (string $value): string => "Hello {$value}",
        );

        self::assertSame('Hello Pandu', $result);
    }

    public function test_get_or_else_returns_value(): void
    {
        $some = new Some('value');

        self::assertSame(
            'value',
            $some->getOrElse('default')
        );
    }

    public function test_or_else_returns_itself(): void
    {
        $some = new Some('value');
        $default = Option::some('default');

        self::assertSame($some, $some->orElse($default));
    }

    public function test_it_is_immutable(): void
    {
        $reflection = new \ReflectionClass(Some::class);

        self::assertTrue($reflection->isReadOnly());
    }
}