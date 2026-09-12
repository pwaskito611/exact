<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Collection;

use Exact\Collection\Set;
use PHPUnit\Framework\TestCase;

final class SetTest extends TestCase
{
    public function testEmptyCreatesEmptySet(): void
    {
        $set = Set::empty();

        self::assertTrue($set->isEmpty());
        self::assertSame(0, $set->size());
    }

    public function testOfRemovesDuplicates(): void
    {
        $set = Set::of(1, 2, 2, 3, 3);

        self::assertSame(
            [1, 2, 3],
            $set->toArray(),
        );
    }

    public function testContainsUsesStrictComparison(): void
    {
        $set = Set::of(1, '1');

        self::assertTrue($set->contains(1));
        self::assertTrue($set->contains('1'));
        self::assertFalse($set->contains(true));
    }

    public function testAddAddsNewValue(): void
    {
        $set = Set::of(1, 2);

        $result = $set->add(3);

        self::assertSame([1, 2], $set->toArray());
        self::assertSame([1, 2, 3], $result->toArray());
    }

    public function testAddDoesNotDuplicateExistingValue(): void
    {
        $set = Set::of(1, 2);

        $result = $set->add(2);

        self::assertSame([1, 2], $result->toArray());
        self::assertSame($set, $result);
    }

    public function testRemoveRemovesValue(): void
    {
        $set = Set::of(1, 2, 3);

        $result = $set->remove(2);

        self::assertSame([1, 3], $result->toArray());
    }

    public function testUnionCombinesSets(): void
    {
        $left = Set::of(1, 2);
        $right = Set::of(2, 3);

        $result = $left->union($right);

        self::assertSame(
            [1, 2, 3],
            $result->toArray(),
        );
    }

    public function testIntersectReturnsCommonValues(): void
    {
        $left = Set::of(1, 2, 3);
        $right = Set::of(2, 3, 4);

        $result = $left->intersect($right);

        self::assertSame(
            [2, 3],
            $result->toArray(),
        );
    }

    public function testMapTransformsValues(): void
    {
        $set = Set::of(1, 2, 3);

        $result = $set->map(
            fn (int $value) => $value * 2,
        );

        self::assertSame(
            [2, 4, 6],
            $result->toArray(),
        );
    }

    public function testMapMaintainsUniqueness(): void
    {
        $set = Set::of(1, 2, 3);

        $result = $set->map(
            fn () => 'same',
        );

        self::assertSame(['same'], $result->toArray());
    }

    public function testOperationsDoNotMutateOriginalSet(): void
    {
        $set = Set::of(1, 2);

        $result = $set->add(3);

        self::assertSame([1, 2], $set->toArray());
        self::assertSame([1, 2, 3], $result->toArray());
    }
}