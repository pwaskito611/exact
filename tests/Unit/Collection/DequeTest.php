<?php

declare(strict_types=1);

namespace Exact\Tests\Unit\Collection;

use Exact\Collection\Deque;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class DequeTest extends TestCase
{
    public function testEmptyCreatesEmptyDeque(): void
    {
        $deque = Deque::empty();

        self::assertSame(0, $deque->count());
        self::assertTrue($deque->isEmpty());
    }

    public function testOfAndFromArrayCreateDeque(): void
    {
        $fromValues = Deque::of(1, 2, 3);
        $fromArray = Deque::fromArray([4, 5, 6]);

        self::assertSame([1, 2, 3], $fromValues->toArray());
        self::assertSame([4, 5, 6], $fromArray->toArray());
    }

    public function testPushFrontAndBackKeepOrder(): void
    {
        $deque = Deque::of(2, 3)
            ->pushFront(1)
            ->pushBack(4);

        self::assertSame([1, 2, 3, 4], $deque->toArray());
        self::assertSame([1, 2, 3, 4], $deque->toArray());
    }

    public function testPrependAndAppendAreAliases(): void
    {
        $deque = Deque::of('b')
            ->prepend('a')
            ->append('c');

        self::assertSame(['a', 'b', 'c'], $deque->toArray());
    }

    public function testPopAndPeekOperationsAreSymmetric(): void
    {
        $deque = Deque::of(1, 2, 3);

        self::assertSame(1, $deque->peekFront());
        self::assertSame(3, $deque->peekBack());
        self::assertSame([2, 3], $deque->popFront()->toArray());
        self::assertSame([1, 2], $deque->popBack()->toArray());
    }

    public function testEmptyDequeThrowsOnPeekAndPop(): void
    {
        $deque = Deque::empty();

        $this->expectException(OutOfBoundsException::class);
        $deque->peekFront();
    }

    public function testAtAndFirstLastUseDequeOrdering(): void
    {
        $deque = Deque::of('a', 'b', 'c');

        self::assertSame('a', $deque->first());
        self::assertSame('c', $deque->last());
        self::assertSame('b', $deque->at(1));
    }

    public function testMapFilterReverseAndTapAreImmutable(): void
    {
        $deque = Deque::of(1, 2, 3, 4);

        $mapped = $deque->map(static fn (int $value): int => $value * 10);
        $filtered = $deque->filter(static fn (int $value): bool => $value % 2 === 0);
        $reversed = $deque->reverse();
        $tapped = $deque->tap(static function (int $value): void {
            // no-op
        });

        self::assertSame([10, 20, 30, 40], $mapped->toArray());
        self::assertSame([2, 4], $filtered->toArray());
        self::assertSame([4, 3, 2, 1], $reversed->toArray());
        self::assertSame([1, 2, 3, 4], $tapped->toArray());
        self::assertSame([1, 2, 3, 4], $deque->toArray());
    }

    public function testEqualsChecksExactOrder(): void
    {
        $a = Deque::of(1, 2, 3);
        $b = Deque::of(1, 2, 3);
        $c = Deque::of(3, 2, 1);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testFromIterableAcceptsAnyIterable(): void
    {
        $deque = Deque::fromIterable(new \ArrayIterator([10, 20, 30]));

        self::assertSame([10, 20, 30], $deque->toArray());
    }
}
