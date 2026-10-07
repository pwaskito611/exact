<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Collection;

use Exact\Collection\Vec;
use Exact\Exception\EmptyCollectionException;
use PHPUnit\Framework\TestCase;

final class VecTest extends TestCase
{
    public function testEmptyCreatesEmptySequence(): void
    {
        $seq = Vec::empty();

        self::assertTrue($seq->isEmpty());
        self::assertSame(0, $seq->size());
    }

    public function testOfCreatesSequence(): void
    {
        $seq = Vec::of(1, 2, 3);

        self::assertSame([1, 2, 3], $seq->toArray());
    }

    public function testFromArrayCreatesSequence(): void
    {
        $seq = Vec::fromArray([1, 2, 3]);

        self::assertSame([1, 2, 3], $seq->toArray());
    }

    public function testSizeReturnsNumberOfElements(): void
    {
        $seq = Vec::of(1, 2, 3);

        self::assertSame(3, $seq->size());
    }

    public function testHeadReturnsFirstElement(): void
    {
        $seq = Vec::of(10, 20, 30);

        self::assertSame(10, $seq->head());
    }

    public function testHeadThrowsOnEmptySequence(): void
    {
        $this->expectException(EmptyCollectionException::class);

        Vec::empty()->head();
    }

    public function testTailReturnsRemainingElements(): void
    {
        $seq = Vec::of(1, 2, 3);

        self::assertSame(
            [2, 3],
            $seq->tail()->toArray(),
        );
    }

    public function testMapTransformsElements(): void
    {
        $result = Vec::of(1, 2, 3)
            ->map(fn (int $value) => $value * 2);

        self::assertSame([2, 4, 6], $result->toArray());
    }

    public function testFilterKeepsMatchingElements(): void
    {
        $result = Vec::of(1, 2, 3, 4)
            ->filter(fn (int $value) => $value % 2 === 0);

        self::assertSame([2, 4], $result->toArray());
    }

    public function testAppendAddsElement(): void
    {
        $seq = Vec::of(1, 2);

        $result = $seq->append(3);

        self::assertSame([1, 2], $seq->toArray());
        self::assertSame([1, 2, 3], $result->toArray());
    }

    public function testPrependAddsElementAtBeginning(): void
    {
        $result = Vec::of(2, 3)->prepend(1);

        self::assertSame([1, 2, 3], $result->toArray());
    }

    public function testContainsUsesStrictComparison(): void
    {
        $seq = Vec::of(1, '1');

        self::assertTrue($seq->contains(1));
        self::assertTrue($seq->contains('1'));
        self::assertFalse($seq->contains(true));
    }

    public function testFoldLeftAccumulatesValues(): void
    {
        $result = Vec::of(1, 2, 3, 4)
            ->foldLeft(
                0,
                fn (int $sum, int $value) => $sum + $value,
            );

        self::assertSame(10, $result);
    }

    public function testOperationsDoNotMutateOriginalSequence(): void
    {
        $seq = Vec::of(1, 2, 3);

        $mapped = $seq->map(
            fn (int $value) => $value * 2,
        );

        self::assertSame([1, 2, 3], $seq->toArray());
        self::assertSame([2, 4, 6], $mapped->toArray());
    }
}