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
        self::assertSame(0, $set->count());
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

    public function testDifferenceReturnsRelativeComplement(): void
    {
        $left = Set::of(1, 2, 3, 4);
        $right = Set::of(2, 4);

        self::assertSame(
            [1, 3],
            $left->difference($right)->toArray(),
        );
    }

    public function testSymmetricDifferenceReturnsExclusiveUnion(): void
    {
        $left = Set::of(1, 2, 3);
        $right = Set::of(2, 3, 4);

        self::assertSame(
            [1, 4],
            $left->symmetricDifference($right)->toArray(),
        );
    }

    public function testRelationChecksWork(): void
    {
        $left = Set::of(1, 2, 3);
        $right = Set::of(2, 3);
        $shared = Set::of(3, 5);
        $disjoint = Set::of(4, 5);

        self::assertTrue($right->isSubsetOf($left));
        self::assertTrue($left->isSupersetOf($right));
        self::assertFalse($left->isDisjoint($shared));
        self::assertTrue($left->isDisjoint($disjoint));
    }

    public function testEqualsComparesSetContentsIgnoringOrder(): void
    {
        $left = Set::of(1, 2, 3);
        $right = Set::of(3, 1, 2);

        self::assertTrue($left->equals($right));
        self::assertFalse($left->equals(Set::of(1, 2, 4)));
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

    public function testFilterRemovesNonMatchingValues(): void
    {
        $set = Set::of(1, 2, 3, 4);

        $filtered = $set->filter(
            static fn (int $value): bool => $value % 2 === 0,
        );

        self::assertSame([2, 4], $filtered->toArray());
    }

    public function testFoldAggregatesValues(): void
    {
        $set = Set::of(1, 2, 3);

        $sum = $set->fold(0, static fn (int $carry, int $value): int => $carry + $value);

        self::assertSame(6, $sum);
    }

    public function testTapPreservesSetAndRunsSideEffect(): void
    {
        $set = Set::of(1, 2, 3);
        $seen = [];

        $result = $set->tap(static function (int $value) use (&$seen): void {
            $seen[] = $value;
        });

        self::assertSame($set, $result);
        self::assertSame([1, 2, 3], $seen);
    }

    public function testMapMaintainsUniqueness(): void
    {
        $set = Set::of(1, 2, 3);

        $result = $set->map(
            fn () => 'same',
        );

        self::assertSame(['same'], $result->toArray());
    }

    public function testJsonSerializationUsesListRepresentation(): void
    {
        $set = Set::of(1, 2, 3);

        self::assertSame([1, 2, 3], $set->jsonSerialize());
    }

    public function testOperationsDoNotMutateOriginalSet(): void
    {
        $set = Set::of(1, 2);

        $result = $set->add(3);

        self::assertSame([1, 2], $set->toArray());
        self::assertSame([1, 2, 3], $result->toArray());
    }
}