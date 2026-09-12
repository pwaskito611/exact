<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Data\Tuple;

use Exact\Data\Tuple\Tuple;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class TupleTest extends TestCase
{
    public function test_it_creates_a_tuple(): void
    {
        $tuple = Tuple::of(1, 'hello', true);

        self::assertInstanceOf(Tuple::class, $tuple);
    }

    public function test_it_returns_value_at_index(): void
    {
        $tuple = Tuple::of(42, 'hello', true);

        self::assertSame(42, $tuple->at(0));
        self::assertSame('hello', $tuple->at(1));
        self::assertTrue($tuple->at(2));
    }

    public function test_it_throws_when_index_does_not_exist(): void
    {
        $tuple = Tuple::of(1, 2, 3);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Index out of range');

        $tuple->at(3);
    }

    public function test_it_throws_when_index_is_negative(): void
    {
        $tuple = Tuple::of(1, 2, 3);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Index out of range');

        $tuple->at(-1);
    }

    public function test_it_returns_number_of_values(): void
    {
        $tuple = Tuple::of(1, 'hello', true);

        self::assertSame(3, $tuple->count());
    }

    public function test_empty_tuple_has_zero_count(): void
    {
        $tuple = Tuple::of();

        self::assertSame(0, $tuple->count());
    }

    public function test_map_transforms_each_value(): void
    {
        $tuple = Tuple::of(1, 2, 3);

        $mapped = $tuple->map(
            fn (int $value): int => $value * 2
        );

        self::assertSame(
            [2, 4, 6],
            $mapped->toArray()
        );
    }

    public function test_map_returns_new_tuple(): void
    {
        $tuple = Tuple::of(1, 2, 3);

        $mapped = $tuple->map(
            fn (int $value): int => $value * 2
        );

        self::assertNotSame($tuple, $mapped);
    }

    public function test_map_preserves_tuple_count(): void
    {
        $tuple = Tuple::of(1, 2, 3);

        $mapped = $tuple->map(
            fn (int $value): int => $value * 2
        );

        self::assertSame($tuple->count(), $mapped->count());
    }

    public function test_it_returns_values_as_array(): void
    {
        $tuple = Tuple::of(1, 'hello', true);

        self::assertSame(
            [1, 'hello', true],
            $tuple->toArray()
        );
    }
}