<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Collection;

use Exact\Collection\NonEmptyVec;
use Exact\Collection\Vec;
use PHPUnit\Framework\TestCase;

final class NonEmptyVecTest extends TestCase
{
    public function testOfCreatesNonEmptySequence(): void
    {
        $seq = NonEmptyVec::of(1, 2, 3);

        self::assertSame([1, 2, 3], $seq->toArray());
    }

    public function testFromArrayCreatesNonEmptySequence(): void
    {
        $seq = NonEmptyVec::fromArray([1, 2, 3]);

        self::assertSame([1, 2, 3], $seq->toArray());
    }

    public function testFromArrayRejectsEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        NonEmptyVec::fromArray([]);
    }

    public function testHeadReturnsFirstElement(): void
    {
        $seq = NonEmptyVec::of(10, 20);

        self::assertSame(10, $seq->head());
    }

    public function testTailReturnsSequence(): void
    {
        $seq = NonEmptyVec::of(1, 2, 3);

        $tail = $seq->tail();

        self::assertInstanceOf(Vec::class, $tail);
        self::assertSame([2, 3], $tail->toArray());
    }

    public function testTailOfSingleElementIsEmpty(): void
    {
        $seq = NonEmptyVec::of(1);

        self::assertSame([], $seq->tail()->toArray());
    }

    public function testSizeReturnsNumberOfElements(): void
    {
        self::assertSame(
            3,
            NonEmptyVec::of(1, 2, 3)->size(),
        );
    }

    public function testMapPreservesNonEmptyInvariant(): void
    {
        $result = NonEmptyVec::of(1, 2, 3)
            ->map(fn (int $value) => $value * 2);

        self::assertInstanceOf(NonEmptyVec::class, $result);
        self::assertSame([2, 4, 6], $result->toArray());
    }

    public function testFilterReturnsSeq(): void
    {
        $result = NonEmptyVec::of(1, 2, 3, 4)
            ->filter(fn (int $value) => $value % 2 === 0);

        self::assertInstanceOf(Vec::class, $result);
        self::assertSame([2, 4], $result->toArray());
    }

    public function testFilterCanReturnEmptySequence(): void
    {
        $result = NonEmptyVec::of(1, 2)
            ->filter(fn () => false);

        self::assertSame([], $result->toArray());
    }

    public function testAppendAddsElement(): void
    {
        $result = NonEmptyVec::of(1, 2)->append(3);

        self::assertSame([1, 2, 3], $result->toArray());
    }

    public function testPrependAddsElement(): void
    {
        $result = NonEmptyVec::of(2, 3)->prepend(1);

        self::assertSame([1, 2, 3], $result->toArray());
    }
}